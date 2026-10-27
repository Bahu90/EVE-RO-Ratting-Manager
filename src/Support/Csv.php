<?php

namespace RattingManager\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A CSV download. UTF-8 with a byte order mark so Excel reads character
 * names right, and numbers left unformatted (dot decimals, no thousands
 * separators) so they can be summed in a spreadsheet.
 */
final class Csv
{
    /**
     * @param string[] $header
     * @param iterable<array> $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row));
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $filename) . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** ISK to two decimals, other numbers as they are, text as is. */
    private static function cell($value)
    {
        if (is_float($value)) {
            return number_format($value, 2, '.', '');
        }

        // A cell starting with =, +, - or @ (or a tab or line break before
        // one) would be run as a formula by a spreadsheet; character names
        // cannot start with those, but a quote in front keeps any such text
        // as text.
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
