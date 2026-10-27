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

    /** "15%" or "12.5%" for 0.15 or 0.125. */
    public static function percent(float $rate): string
    {
        return rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') . '%';
    }
}
