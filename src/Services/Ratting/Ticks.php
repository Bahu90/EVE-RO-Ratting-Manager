<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Reading ticks for the figures that cannot come from the daily summaries.
 *
 * A year of a big corporation is hundreds of thousands of ticks, so they are
 * streamed rather than held, and the time comes back as unix seconds in `ts`
 * so the loops work on integers instead of parsing a date per row. Ticks are
 * stored in EVE time, which is UTC, so `ts` is what strtotime() would give.
 */
final class Ticks
{
    /**
     * @param string[] $columns tick columns besides the time
     */
    public static function stream(Scope $scope, Carbon $from, Carbon $to, array $columns, bool $oldestFirst = false): LazyCollection
    {
        $query = $scope->apply(DB::table('ratting_manager_ticks'))
            ->whereBetween('ticked_at', [$from, $to])
            ->select($columns)
            ->selectRaw("TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', ticked_at) as ts");

        if ($oldestFirst) {
            $query->orderBy('ticked_at');
        }

        return $query->cursor();
    }

    /**
     * The characters with ratting in the scope between two times, from the
     * daily summaries, so their players can be loaded in one go before the
     * ticks are streamed.
     *
     * @return int[]
     */
    public static function characters(Scope $scope, Carbon $from, Carbon $to): array
    {
        return $scope->apply(DB::table('ratting_manager_daily_summaries'))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->distinct()
            ->pluck('character_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Rats killed per NPC type, a fleet's shared payout counted once, with
     * what is known of each type.
     *
     * @return array<int, array{npc_type_id: int, quantity: int, name: ?string, faction: string, size: string, rank: string}>
     *         most killed first
     */
    public static function kills(Scope $scope, Carbon $from, Carbon $to): array
    {
        $key = 'kills:' . $scope->cacheKey() . ':' . FigureCache::range($from, $to);

        return FigureCache::remember($key, function () use ($scope, $from, $to) {
            $quantities = self::killsByType($scope, $from, $to)->pluck('quantity', 'npc_type_id');
            $types = DB::table('ratting_manager_npc_types')
                ->whereIn('type_id', $quantities->keys()->all() ?: [0])
                ->get(['type_id', 'name', 'faction', 'size', 'rank'])
                ->keyBy('type_id');

            $rows = [];
            foreach ($quantities as $typeId => $quantity) {
                $type = $types->get($typeId);
                $rows[] = [
                    'npc_type_id' => (int) $typeId,
                    'quantity' => (int) $quantity,
                    'name' => $type->name ?? null,
                    'faction' => $type->faction ?? 'other',
                    'size' => $type->size ?? 'unknown',
                    'rank' => $type->rank ?? 'regular',
                ];
            }
            usort($rows, fn ($a, $b) => $b['quantity'] <=> $a['quantity']);

            return $rows;
        });
    }

    /**
     * The query behind kills(): npc_type_id and quantity.
     *
     * One whole corporation reads its daily kill totals. Anything else reads
     * the ticks: the daily totals are not kept per character, and a fleet
     * with members in two corporations is in both corporations' totals.
     * Every tick in a kill group paid for the same kills, so one tick per
     * group stands for the group.
     */
    private static function killsByType(Scope $scope, Carbon $from, Carbon $to): Builder
    {
        if ($scope->characters === null && count($scope->corporations) === 1) {
            return DB::table('ratting_manager_daily_kills')
                ->where('corporation_id', $scope->corporations[0])
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->groupBy('npc_type_id')
                ->selectRaw('npc_type_id, SUM(quantity) as quantity');
        }

        $groups = $scope->apply(DB::table('ratting_manager_ticks'))
            ->whereBetween('ticked_at', [$from, $to])
            ->whereNotNull('kill_group')
            ->groupBy('kill_group')
            ->selectRaw('MIN(id) as id');

        return DB::table('ratting_manager_tick_kills as k')
            ->joinSub($groups, 'g', 'g.id', '=', 'k.tick_id')
            ->groupBy('k.npc_type_id')
            ->selectRaw('k.npc_type_id, SUM(k.quantity) as quantity');
    }
}
