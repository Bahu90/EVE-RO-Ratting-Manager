<?php

namespace RattingManager\Services\Character;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The corporation a character was in at a given moment.
 *
 * Character journal rows do not say which corporation the ratter was in, and
 * SeAT never fills tax_receiver_id, so the employment history decides. A
 * member who later leaves keeps their ticks in the corporation they ratted
 * for.
 */
class CorporationOnDate
{
    /** @var array<int, array<int, array{0: int, 1: int}>> character_id => [[start timestamp, corporation_id], ...] oldest first */
    private array $history = [];

    /** @var array<int, int> character_id => current corporation_id */
    private array $current = [];

    /** @param int[] $characterIds */
    public function load(array $characterIds): void
    {
        $missing = array_values(array_diff($characterIds, array_keys($this->history)));
        if ($missing === []) {
            return;
        }

        foreach (array_chunk($missing, 1000) as $chunk) {
            foreach ($chunk as $id) {
                $this->history[$id] = [];
            }

            $rows = DB::table('character_corporation_histories')
                ->whereIn('character_id', $chunk)
                ->orderBy('start_date')
                ->get(['character_id', 'corporation_id', 'start_date']);

            foreach ($rows as $row) {
                $this->history[(int) $row->character_id][] = [
                    Carbon::parse($row->start_date)->getTimestamp(),
                    (int) $row->corporation_id,
                ];
            }

            $this->current += DB::table('character_affiliations')
                ->whereIn('character_id', $chunk)
                ->pluck('corporation_id', 'character_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
    }

    public function at(int $characterId, $moment): ?int
    {
        $this->load([$characterId]);

        $time = Carbon::parse($moment)->getTimestamp();
        $found = null;

        foreach ($this->history[$characterId] as [$start, $corporationId]) {
            if ($start > $time) {
                break;
            }
            $found = $corporationId;
        }

        // No history row reaches back that far, or SeAT has none at all: the
        // current corporation is the best that is known.
        return $found ?? ($this->current[$characterId] ?? null);
    }
}
