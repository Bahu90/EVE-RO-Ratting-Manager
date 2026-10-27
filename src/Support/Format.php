<?php

namespace RattingManager\Support;

final class Format
{
    /**
     * ISK the way the corporation's ratting reports write it: two decimals
     * and a unit, 57.38 B, 788.07 M, 660.60 K; below a thousand, whole ISK.
     * The column or label says it is ISK, so the amount does not repeat it.
     */
    public static function isk($value): string
    {
        $value = (float) $value;
        $abs = abs($value);

        foreach ([[1e12, 'T'], [1e9, 'B'], [1e6, 'M'], [1e3, 'K']] as [$size, $unit]) {
            if ($abs >= $size) {
                return number_format($value / $size, 2) . ' ' . $unit;
            }
        }

        return number_format($value, 0);
    }

    public static function hours(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm', $m);
    }
}
