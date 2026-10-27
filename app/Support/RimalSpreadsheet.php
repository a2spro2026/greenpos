<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Excel natif (SpreadsheetML 2003, .xls) sans dépendance externe :
 * ouvert directement par Excel / LibreOffice avec colonnes numériques.
 */
class RimalSpreadsheet
{
    public static function download(string $filename, string $sheet, array $columns, array $rows, array $totals = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheet, $columns, $rows, $totals) {
            $e = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $cell = function ($v) use ($e) {
                if (is_int($v) || is_float($v)) {
                    return '<Cell><Data ss:Type="Number">'.$v.'</Data></Cell>';
                }

                return '<Cell><Data ss:Type="String">'.$e($v ?? '').'</Data></Cell>';
            };
            echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            echo '<?mso-application progid="Excel.Sheet"?>'."\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Styles><Style ss:ID="h"><Font ss:Bold="1"/><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/></Style></Styles>';
            echo '<Worksheet ss:Name="'.$e(mb_substr($sheet, 0, 31)).'"><Table>';
            echo '<Row>';
            foreach ($columns as $label) {
                echo '<Cell ss:StyleID="h"><Data ss:Type="String">'.$e($label).'</Data></Cell>';
            }
            echo '</Row>';
            foreach ($rows as $row) {
                echo '<Row>';
                foreach (array_keys($columns) as $k) {
                    echo $cell($row[$k] ?? '');
                }
                echo '</Row>';
            }
            if ($totals) {
                echo '<Row>';
                $first = true;
                foreach (array_keys($columns) as $k) {
                    echo array_key_exists($k, $totals) ? $cell((float) $totals[$k]) : $cell($first ? 'TOTAL' : '');
                    $first = false;
                }
                echo '</Row>';
            }
            echo '</Table></Worksheet></Workbook>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }
}
