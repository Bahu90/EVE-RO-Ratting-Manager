<?php

namespace RattingManager\Support;

use Illuminate\Support\Facades\DB;

/**
 * Small lookups about the corporations a page covers, so the pages can
 * speak of "EVE-RO" when they cover one corporation and stay general when
 * they cover several.
 */
final class Corporations
{
    /**
     * What a page head calls its corporations: the ticker (or name) when
     * there is exactly one, otherwise the plugin's name.
     *
     * @param int[] $corporations
     */
    public static function brand(array $corporations): string
    {
        if (count($corporations) === 1) {
            $row = DB::table('corporation_infos')->where('corporation_id', (int) $corporations[0])->first(['ticker', 'name']);
            $label = $row ? trim((string) ($row->ticker ?: $row->name)) : '';
            if ($label !== '') {
                return $label;
            }
        }

        return trans('ratting-manager::common.plugin_name');
    }

    /**
     * The corporation's tax rate as SeAT holds it now (0.15 for 15%), or null
     * for several corporations or one SeAT knows no rate for.
     *
     * @param int[] $corporations
     */
    public static function taxRate(array $corporations): ?float
    {
        if (count($corporations) !== 1) {
            return null;
        }

        $rate = DB::table('corporation_infos')->where('corporation_id', (int) $corporations[0])->value('tax_rate');

        return $rate === null ? null : (float) $rate;
    }

    /**
     * Mark the players who have left: SeAT knows where their characters are
     * now, and none of them is in a tracked corporation. Their ratting still
     * counts where they earned it; the mark explains why they are listed.
     * Players SeAT has no affiliation for are not marked.
     *
     * @param array<int, array> $rows player rows with a 'characters' list
     * @param int[] $tracked
     * @return array<int, array> the rows, each with 'left' => bool
     */
    public static function markLeft(array $rows, array $tracked): array
    {
        // The characters that ratted, and the main, which may be in the
        // corporation while its ratting alts are not.
        $of = fn (array $row) => array_merge($row['characters'] ?? [], isset($row['main_id']) ? [$row['main_id']] : []);

        $characters = [];
        foreach ($rows as $row) {
            foreach ($of($row) as $id) {
                $characters[] = (int) $id;
            }
        }

        $now = [];
        foreach (array_chunk(array_values(array_unique($characters)), 1000) as $chunk) {
            foreach (DB::table('character_affiliations')->whereIn('character_id', $chunk)->get(['character_id', 'corporation_id']) as $a) {
                $now[(int) $a->character_id] = (int) $a->corporation_id;
            }
        }

        foreach ($rows as &$row) {
            $known = false;
            $inside = false;
            foreach ($of($row) as $id) {
                if (isset($now[(int) $id])) {
                    $known = true;
                    $inside = $inside || in_array($now[(int) $id], $tracked, true);
                }
            }
            $row['left'] = $known && !$inside;
        }
        unset($row);

        return $rows;
    }

    /** "15%" or "12.5%" for 0.15 or 0.125. */
    public static function percent(float $rate): string
    {
        return rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') . '%';
    }
}
