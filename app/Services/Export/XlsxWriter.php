<?php

namespace App\Services\Export;

use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Export Excel, écrit sans dépendance tierce.
 *
 * Un `.xlsx` est une archive ZIP de fichiers XML : `ext-zip` suffit à en
 * produire un que Excel, LibreOffice et Google Sheets ouvrent nativement. On
 * s'en tient au strict nécessaire — un onglet, un en-tête en gras, des colonnes
 * dimensionnées — plutôt que de tirer une librairie de mise en forme complète
 * pour trois tableaux.
 *
 * Les chaînes sont écrites en ligne (`inlineStr`). La table des chaînes
 * partagées d'Excel économiserait quelques kilo-octets sur des milliers de
 * lignes, au prix d'une passe supplémentaire sur des exports qui en comptent
 * quelques centaines.
 */
class XlsxWriter implements SpreadsheetWriter
{
    /**
     * Style de cellule normal, puis en gras : leur index dans `cellXfs`.
     */
    private const STYLE_BODY = 0;

    private const STYLE_HEADING = 1;

    /**
     * Bornes de largeur d'une colonne, en nombre de caractères.
     */
    private const MIN_WIDTH = 10;

    private const MAX_WIDTH = 50;

    public function download(Spreadsheet $sheet, string $filename): BinaryFileResponse
    {
        return response()
            ->download($this->write($sheet), $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    /**
     * Assemble l'archive et rend le chemin du fichier temporaire.
     *
     * `ZipArchive` écrit sur disque, pas en mémoire : le fichier est supprimé
     * une fois la réponse envoyée.
     */
    private function write(Spreadsheet $sheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'salon-export-');

        if ($path === false) {
            throw new RuntimeException("Impossible de créer le fichier temporaire de l'export.");
        }

        $archive = new ZipArchive;

        if ($archive->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Impossible d'ouvrir l'archive de l'export.");
        }

        $archive->addFromString('[Content_Types].xml', $this->contentTypes());
        $archive->addFromString('_rels/.rels', $this->packageRelationships());
        $archive->addFromString('xl/workbook.xml', $this->workbook($sheet->title));
        $archive->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $archive->addFromString('xl/styles.xml', $this->styles());
        $archive->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($sheet));

        $archive->close();

        return $path;
    }

    private function contentTypes(): string
    {
        return $this->declaration()
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function packageRelationships(): string
    {
        return $this->declaration()
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $title): string
    {
        return $this->declaration()
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->escape($this->sheetName($title)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationships(): string
    {
        return $this->declaration()
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /**
     * Deux polices, deux styles : le corps du tableau et son en-tête.
     *
     * Excel exige les deux remplissages `none` et `gray125` en tête de table,
     * même inutilisés — leur absence lui fait refuser le classeur.
     */
    private function styles(): string
    {
        return $this->declaration()
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'</fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * L'onglet lui-même : en-tête figé, filtre automatique, puis les lignes.
     *
     * L'ordre des éléments est imposé par le schéma : `sheetViews`, `cols`,
     * `sheetData`, puis `autoFilter`.
     */
    private function worksheet(Spreadsheet $sheet): string
    {
        $lastColumn = $this->columnName(max(1, count($sheet->headings)));

        $rows = $this->rowXml(1, $sheet->headings, self::STYLE_HEADING);
        $number = 1;

        foreach ($sheet->rows as $row) {
            $rows .= $this->rowXml(++$number, $row, self::STYLE_BODY);
        }

        return $this->declaration()
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0">'
            .'<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .$this->columnsXml($sheet)
            .'<sheetData>'.$rows.'</sheetData>'
            .'<autoFilter ref="A1:'.$lastColumn.$number.'"/>'
            .'</worksheet>';
    }

    /**
     * Largeur de chaque colonne, déduite de son contenu le plus long.
     *
     * Sans cela toutes les colonnes sortent à la largeur par défaut, et les
     * noms de mission s'affichent tronqués dès l'ouverture.
     */
    private function columnsXml(Spreadsheet $sheet): string
    {
        if ($sheet->headings === []) {
            return '';
        }

        $columns = '';

        foreach (array_values($sheet->headings) as $index => $heading) {
            $longest = mb_strlen($heading);

            foreach ($sheet->rows as $row) {
                $longest = max($longest, mb_strlen((string) (array_values($row)[$index] ?? '')));
            }

            $width = min(self::MAX_WIDTH, max(self::MIN_WIDTH, $longest + 2));

            $columns .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        return '<cols>'.$columns.'</cols>';
    }

    /**
     * @param  array<int, string|int|null>  $values
     */
    private function rowXml(int $number, array $values, int $style): string
    {
        $cells = '';

        foreach (array_values($values) as $index => $value) {
            $reference = $this->columnName($index + 1).$number;

            if (is_int($value)) {
                // Un entier écrit en numérique reste additionnable dans Excel :
                // les jauges des exports doivent pouvoir se sommer.
                $cells .= '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';

                continue;
            }

            $text = (string) $value;

            if ($text === '') {
                $cells .= '<c r="'.$reference.'" s="'.$style.'"/>';

                continue;
            }

            $cells .= '<c r="'.$reference.'" s="'.$style.'" t="inlineStr">'
                .'<is><t xml:space="preserve">'.$this->escape($text).'</t></is></c>';
        }

        return '<row r="'.$number.'">'.$cells.'</row>';
    }

    /**
     * Le nom d'une colonne à partir de son rang : 1 donne A, 27 donne AA.
     */
    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }

    /**
     * Excel refuse un onglet de plus de 31 caractères, et ces caractères-là.
     */
    private function sheetName(string $title): string
    {
        $name = trim(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $title));

        return mb_substr($name, 0, 31) ?: 'Feuille 1';
    }

    /**
     * Texte prêt à être inséré dans le XML.
     *
     * Les caractères de contrôle sont interdits par XML 1.0 : un seul d'entre
     * eux, hérité d'un copier-coller dans un champ de saisie, rendrait le
     * classeur entier illisible.
     */
    private function escape(string $value): string
    {
        $printable = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return htmlspecialchars($printable, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function declaration(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
    }
}
