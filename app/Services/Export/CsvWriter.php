<?php

namespace App\Services\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV, taillé pour être ouvert par un double-clic sous Excel français.
 *
 * Deux détails font toute la différence à l'usage : le séparateur
 * point-virgule, qu'Excel attend en locale française, et la marque d'ordre des
 * octets, sans laquelle il lit le fichier en ANSI et casse tous les accents.
 */
class CsvWriter implements SpreadsheetWriter
{
    private const DELIMITER = ';';

    /**
     * Marque d'ordre des octets UTF-8.
     */
    private const BOM = "\u{FEFF}";

    public function download(Spreadsheet $sheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheet): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, self::BOM);

            $this->putRow($output, $sheet->headings);

            foreach ($sheet->rows as $row) {
                $this->putRow($output, $row);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $output
     * @param  array<int, string|int|null>  $row
     */
    private function putRow($output, array $row): void
    {
        // Echappement desactive : la sequence antislash de `fputcsv` n'est pas
        // du CSV standard, et Excel l'affiche telle quelle dans la cellule.
        fputcsv($output, array_map(
            fn (string|int|null $value): string => (string) $value,
            $row,
        ), self::DELIMITER, '"', '');
    }
}
