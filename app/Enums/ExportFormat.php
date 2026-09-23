<?php

namespace App\Enums;

/**
 * Les deux formats de sortie demandés par le cahier des charges.
 *
 * Le CSV s'ouvre partout et se réimporte ; le classeur Excel se distribue tel
 * quel à un responsable de poste. Les deux portent exactement les mêmes
 * colonnes : seule l'enveloppe change.
 */
enum ExportFormat: string
{
    case Xlsx = 'xlsx';
    case Csv = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::Xlsx => 'Excel',
            self::Csv => 'CSV',
        };
    }

    public function extension(): string
    {
        return $this->value;
    }

    public function contentType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv; charset=UTF-8',
        };
    }
}
