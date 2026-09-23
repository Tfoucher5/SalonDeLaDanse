<?php

namespace App\Services\Export;

/**
 * Une feuille de calcul, indépendante de son format de sortie.
 *
 * Les données sont construites une seule fois par `EditionExports` ; le CSV et
 * le classeur Excel ne font que l'habiller. C'est ce qui garantit que les deux
 * formats portent les mêmes colonnes et les mêmes lignes.
 */
final readonly class Spreadsheet
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string|int|null>>  $rows
     */
    public function __construct(
        public string $title,
        public array $headings,
        public array $rows,
    ) {}

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }
}
