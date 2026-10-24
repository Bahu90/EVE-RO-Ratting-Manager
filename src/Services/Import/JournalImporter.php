<?php

namespace RattingManager\Services\Import;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RattingManager\Models\EssPayout;
use RattingManager\Models\Tick;
use RattingManager\Services\Character\AffiliationResolutionService;
use RattingManager\Services\Character\CorporationOnDate;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Services\Ratting\Progress;
use RattingManager\Services\Universe\SolarSystems;

/**
 * Brings bounty ticks and ESS payouts in from SeAT's wallet journals.
 *
 * The corporation journal holds the corp's cut of every member's tick and
 * names the ratter in second_party_id, so it sees members who never added a
 * wallet token. The character journal holds what the character received. The
 * same tick shows up in both at the same second, and whichever arrives first
 * creates the row the other pairs onto. Rows are keyed on their journal ids,
 * so reading the same days again changes nothing.
 */
class JournalImporter
{
    private const STREAMS = [
        'bounty' => [
            'ref_types' => ['bounty_prizes', 'bounty_prize'],
            'model' => Tick::class,
            'time' => 'ticked_at',
        ],
        'ess' => [
            'ref_types' => ['ess_escrow_transfer'],
            'model' => EssPayout::class,
            'time' => 'paid_at',
        ],
    ];

    /**
     * Paired rows have shared the same second on every install checked. The
     * small allowance is there in case a payout is ever written a moment
     * apart; a character's ticks are minutes apart, so it cannot pick the
     * wrong one.
     */
    public const PAIR_TOLERANCE_SECONDS = 10;

    private const COLUMNS = [
        'id', 'date', 'ref_type', 'second_party_id', 'amount', 'reason',
        'context_id', 'context_id_type', 'description',
    ];

    private Settings $settings;
    private CorporationOnDate $corporations;
    private KillRecorder $kills;
    private SolarSystems $systems;
    private AffiliationResolutionService $lookups;

    /** @var array<int, string> character_id => name read from a description */
    private array $names = [];

    public function __construct(
        Settings $settings,
        CorporationOnDate $corporations,
        KillRecorder $kills,
        SolarSystems $systems,
        AffiliationResolutionService $lookups
    ) {
        $this->settings = $settings;
        $this->corporations = $corporations;
        $this->kills = $kills;
        $this->systems = $systems;
        $this->lookups = $lookups;
    }

    /** How many rows importCorporation() will read, for a progress bar. */
    public function corporationRowCount(Carbon $since): int
    {
        $corporations = $this->settings->configuredCorporations();

        return $corporations === [] ? 0 : DB::table('corporation_wallet_journals')
            ->whereIn('corporation_id', $corporations)
            ->whereIn('ref_type', self::refTypes())
            ->where('date', '>=', $since)
            ->count();
    }

    /** How many rows importCharacter() will read, for a progress bar. */
    public function characterRowCount(Carbon $since): int
    {
        $corporations = $this->settings->configuredCorporations();
        if ($corporations === []) {
            return 0;
        }

        $total = 0;
        foreach (array_chunk($this->candidateCharacters($corporations), 1000) as $characters) {
            $total += DB::table('character_wallet_journals')
                ->whereIn('character_id', $characters)
                ->whereIn('ref_type', self::refTypes())
                ->where('date', '>=', $since)
                ->count();
        }

        return $total;
    }

    public function importCorporation(Carbon $since, ?Progress $progress = null): array
    {
        $progress ??= Progress::none();
        $corporations = $this->settings->configuredCorporations();
        $report = $this->emptyReport();

        if ($corporations === []) {
            $report['note'] = 'No corporation is configured.';
            return $report;
        }

        foreach (self::STREAMS as $stream => $definition) {
            DB::table('corporation_wallet_journals')
                ->whereIn('corporation_id', $corporations)
                ->whereIn('ref_type', $definition['ref_types'])
                ->where('date', '>=', $since)
                ->select(array_merge(['corporation_id'], self::COLUMNS))
                ->chunkById(1000, function ($rows) use ($stream, $corporations, &$report, $progress) {
                    $this->applyChunk('corporation', $stream, $rows, $corporations, $report);
                    $progress->advance(count($rows));
                }, 'id');
        }

        $this->storeNames();

        return $report;
    }

    public function importCharacter(Carbon $since, ?Progress $progress = null): array
    {
        $progress ??= Progress::none();
        $corporations = $this->settings->configuredCorporations();
        $report = $this->emptyReport();

        if ($corporations === []) {
            $report['note'] = 'No corporation is configured.';
            return $report;
        }

        foreach (array_chunk($this->candidateCharacters($corporations), 1000) as $characters) {
            $this->corporations->load($characters);

            foreach (self::STREAMS as $stream => $definition) {
                DB::table('character_wallet_journals')
                    ->whereIn('character_id', $characters)
                    ->whereIn('ref_type', $definition['ref_types'])
                    ->where('date', '>=', $since)
                    ->select(array_merge(['character_id'], self::COLUMNS))
                    ->chunkById(1000, function ($rows) use ($stream, $corporations, &$report, $progress) {
                        $this->applyChunk('character', $stream, $rows, $corporations, $report);
                        $progress->advance(count($rows));
                    }, 'id');
            }
        }

        $this->storeNames();

        return $report;
    }

    /** @return string[] every ref type either stream reads */
    private static function refTypes(): array
    {
        return array_merge(...array_values(array_column(self::STREAMS, 'ref_types')));
    }

    /**
     * Everyone who has been in a configured corporation at some point. Their
     * corporation on the day decides whether a row counts.
     *
     * @param int[] $corporations
     * @return int[]
     */
    private function candidateCharacters(array $corporations): array
    {
        return DB::table('character_affiliations')
            ->whereIn('corporation_id', $corporations)
            ->pluck('character_id')
            ->merge(
                DB::table('character_corporation_histories')
                    ->whereIn('corporation_id', $corporations)
                    ->pluck('character_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function applyChunk(string $side, string $stream, Collection $rows, array $corporations, array &$report): void
    {
        $definition = self::STREAMS[$stream];
        /** @var class-string<Tick|EssPayout> $model */
        $model = $definition['model'];
        $timeColumn = $definition['time'];
        $ownColumn = $side . '_journal_id';
        $otherColumn = ($side === 'corporation' ? 'character' : 'corporation') . '_journal_id';

        $items = [];
        foreach ($rows as $row) {
            $report[$stream]['read']++;
            $item = $this->normalise($side, $stream, $row, $corporations);

            if (is_string($item)) {
                $report[$stream]['skipped'][$item] = ($report[$stream]['skipped'][$item] ?? 0) + 1;
                continue;
            }

            $items[] = $item;
        }

        if ($items === []) {
            return;
        }

        $existing = $model::whereIn($ownColumn, array_column($items, 'journal_id'))
            ->get()
            ->keyBy($ownColumn);

        $times = array_map(fn ($item) => strtotime($item['time']), $items);
        $candidates = $model::whereIn('character_id', array_unique(array_column($items, 'character_id')))
            ->whereBetween($timeColumn, [
                date('Y-m-d H:i:s', min($times) - self::PAIR_TOLERANCE_SECONDS),
                date('Y-m-d H:i:s', max($times) + self::PAIR_TOLERANCE_SECONDS),
            ])
            ->whereNull($ownColumn)
            ->get()
            ->groupBy('character_id');

        DB::transaction(function () use ($items, $existing, &$candidates, $model, $side, $stream, $timeColumn, $ownColumn, &$report) {
            foreach ($items as $item) {
                $record = $existing->get($item['journal_id']);

                if ($record) {
                    $this->fill($record, $side, $item, false);
                    if ($record->isDirty()) {
                        $record->save();
                        $report[$stream]['updated']++;
                    }
                } elseif ($record = $this->takeMatch($candidates, $item, $timeColumn)) {
                    $record->{$ownColumn} = $item['journal_id'];
                    $this->fill($record, $side, $item, true);
                    $record->save();
                    $report[$stream]['paired']++;
                } else {
                    $record = new $model([
                        'character_id' => $item['character_id'],
                        'corporation_id' => $item['corporation_id'],
                        'solar_system_id' => $item['system_id'],
                        $timeColumn => $item['time'],
                        'kind' => $side,
                        $ownColumn => $item['journal_id'],
                    ]);
                    $this->fill($record, $side, $item, false);
                    $record->save();
                    $report[$stream]['created']++;
                }

                if ($stream === 'bounty') {
                    $this->kills->record($record, $item['reason']);
                }
            }
        });
    }

    /** @return array<string, mixed>|string the row ready to apply, or why it was left out */
    private function normalise(string $side, string $stream, $row, array $corporations)
    {
        $characterId = $side === 'corporation' ? (int) $row->second_party_id : (int) $row->character_id;
        if ($characterId <= 0) {
            return 'no_character';
        }

        if ((float) $row->amount <= 0) {
            return 'not_a_payment';
        }

        $text = JournalText::read($row->description);
        $systemId = ($row->context_id_type === 'system_id' && $row->context_id)
            ? (int) $row->context_id
            : $this->systems->idForName($text['system']);

        if ($stream === 'bounty' && !$systemId) {
            return 'no_system';
        }

        $time = substr((string) $row->date, 0, 19);

        if ($side === 'corporation') {
            $corporationId = (int) $row->corporation_id;
        } else {
            $corporationId = $this->corporations->at($characterId, $time);
            if (!$corporationId || !in_array($corporationId, $corporations, true)) {
                return 'other_corporation';
            }
        }

        if ($text['name'] !== null) {
            $this->names[$characterId] = $text['name'];
        }

        return [
            'journal_id' => (int) $row->id,
            'character_id' => $characterId,
            'corporation_id' => $corporationId,
            'system_id' => $systemId,
            'time' => $time,
            'amount' => round((float) $row->amount, 2),
            'reason' => $row->reason,
        ];
    }

    /**
     * Put one journal's side of the figures on a row. The corporation row is
     * the authority on which corporation the tick belongs to; the character
     * row on what the character received.
     */
    private function fill($record, string $side, array $item, bool $pairing): void
    {
        if ($side === 'corporation') {
            $record->corp_tax_amount = $item['amount'];
            $record->corporation_id = $item['corporation_id'];
            if ($record->kind === 'corporation' || !$record->exists) {
                // Worked out once the corporation's tax rate for the day is known.
                $record->net_is_estimated = true;
            }
        } else {
            $record->net_amount = $item['amount'];
            $record->net_is_estimated = false;
            $record->tax_rate = null;
        }

        if ($pairing) {
            $record->kind = 'paired';
        }
    }

    private function takeMatch(Collection &$candidates, array $item, string $timeColumn)
    {
        $pool = $candidates->get($item['character_id']);
        if (!$pool) {
            return null;
        }

        $target = strtotime($item['time']);
        $bestKey = null;
        $bestGap = null;

        foreach ($pool as $key => $candidate) {
            if ((int) $candidate->solar_system_id !== (int) $item['system_id']) {
                continue;
            }

            $gap = abs($candidate->{$timeColumn}->getTimestamp() - $target);
            if ($gap <= self::PAIR_TOLERANCE_SECONDS && ($bestGap === null || $gap < $bestGap)) {
                $bestKey = $key;
                $bestGap = $gap;
            }
        }

        if ($bestKey === null) {
            return null;
        }

        $match = $pool->get($bestKey);
        $candidates->put($item['character_id'], $pool->forget($bestKey));

        return $match;
    }

    /**
     * Hand the names descriptions gave to the character lookups, so untracked
     * ratters are named on every page without asking ESI.
     */
    private function storeNames(): void
    {
        $this->lookups->rememberJournalNames($this->names);
        $this->names = [];
    }

    private function emptyReport(): array
    {
        $stream = ['read' => 0, 'created' => 0, 'paired' => 0, 'updated' => 0, 'skipped' => []];

        return ['bounty' => $stream, 'ess' => $stream];
    }
}
