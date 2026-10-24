<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RattingManager\Models\RattingSession;
use RattingManager\Models\Tick;
use RattingManager\Services\Configuration\Settings;

/**
 * Groups each character's ticks into sessions: a run of ticks with no gap
 * longer than the session gap.
 *
 * A session can start before the window being rebuilt, so the rebuild for a
 * character begins at the first tick of whatever session was still open
 * when the window starts.
 */
class SessionBuilder
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /** How many characters rebuild() will go through, for a progress bar. */
    public function characterCount(Carbon $since): int
    {
        return Tick::where('ticked_at', '>=', $since)->distinct()->count('character_id');
    }

    public function rebuild(Carbon $since, ?Progress $progress = null): int
    {
        $progress ??= Progress::none();
        $gap = max(20, (int) $this->settings->get('data.session_gap_minutes')) * 60;

        $characters = Tick::where('ticked_at', '>=', $since)
            ->distinct()
            ->pluck('character_id');

        $built = 0;
        foreach ($characters as $characterId) {
            $built += $this->rebuildCharacter((int) $characterId, $since, $gap);
            $progress->advance();
        }

        return $built;
    }

    private function rebuildCharacter(int $characterId, Carbon $since, int $gap): int
    {
        $from = $since->copy();

        $open = RattingSession::where('character_id', $characterId)
            ->where('ended_at', '>=', $since->copy()->subSeconds($gap))
            ->where('started_at', '<', $since)
            ->orderBy('started_at')
            ->first();

        if ($open) {
            $from = $open->started_at->copy();
        }

        $ticks = Tick::where('character_id', $characterId)
            ->where('ticked_at', '>=', $from)
            ->orderBy('ticked_at')
            ->get(['id', 'corporation_id', 'solar_system_id', 'ticked_at', 'net_amount', 'net_is_estimated', 'npc_kills']);

        return DB::transaction(function () use ($characterId, $from, $ticks, $gap) {
            $old = RattingSession::where('character_id', $characterId)->where('ended_at', '>=', $from)->pluck('id');
            Tick::whereIn('session_id', $old)->update(['session_id' => null]);
            RattingSession::whereIn('id', $old)->delete();

            $groups = [];
            $current = [];
            $last = null;
            foreach ($ticks as $tick) {
                $t = $tick->ticked_at->getTimestamp();
                if ($last !== null && $t - $last > $gap) {
                    $groups[] = $current;
                    $current = [];
                }
                $current[] = $tick;
                $last = $t;
            }
            if ($current !== []) {
                $groups[] = $current;
            }

            foreach ($groups as $group) {
                $this->store($characterId, $group);
            }

            return count($groups);
        });
    }

    private function store(int $characterId, array $ticks): void
    {
        $first = $ticks[0];
        $last = end($ticks);

        $minutes = ActiveTime::minutes(array_map(fn ($t) => $t->ticked_at->getTimestamp(), $ticks));
        $net = array_sum(array_map(fn ($t) => (float) $t->net_amount, $ticks));
        $estimated = array_sum(array_map(fn ($t) => $t->net_is_estimated ? (float) $t->net_amount : 0.0, $ticks));

        $bySystem = [];
        foreach ($ticks as $t) {
            $bySystem[$t->solar_system_id] = ($bySystem[$t->solar_system_id] ?? 0) + 1;
        }
        arsort($bySystem);

        $session = RattingSession::create([
            'character_id' => $characterId,
            'corporation_id' => $first->corporation_id,
            'started_at' => $first->ticked_at->copy()->subMinutes(ActiveTime::TICK_MINUTES),
            'ended_at' => $last->ticked_at,
            'minutes' => $minutes,
            'tick_count' => count($ticks),
            'primary_system_id' => (int) array_key_first($bySystem),
            'system_count' => count($bySystem),
            'net_amount' => round($net, 2),
            'estimated_amount' => round($estimated, 2),
            'npc_kills' => array_sum(array_map(fn ($t) => (int) $t->npc_kills, $ticks)),
            'isk_per_hour' => $minutes > 0 ? round($net / ($minutes / 60), 2) : 0,
        ]);

        Tick::whereIn('id', array_map(fn ($t) => $t->id, $ticks))->update(['session_id' => $session->id]);
    }
}
