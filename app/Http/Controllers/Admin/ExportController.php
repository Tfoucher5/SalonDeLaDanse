<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportRequest;
use App\Models\Edition;
use App\Services\Export\CsvWriter;
use App\Services\Export\EditionExports;
use App\Services\Export\SpreadsheetWriter;
use App\Services\Export\XlsxWriter;
use Symfony\Component\HttpFoundation\Response;

/**
 * La sortie des données : trois feuilles, deux formats.
 *
 * Il n'y a pas d'écran d'export : on exporte depuis la page qu'on regarde, par
 * sa fenêtre d'export, qui transmet les critères de l'écran. Ce qu'on voit est
 * ce qu'on télécharge, sinon l'export devient un piège.
 */
class ExportController extends Controller
{
    public function __construct(private readonly EditionExports $exports) {}

    public function download(ExportRequest $request): Response
    {
        $edition = Edition::current();

        abort_if($edition === null, 404, "Aucune édition n'est ouverte : il n'y a rien à exporter.");

        $dataset = $request->exportDataset();
        $format = $request->exportFormat();

        return $this->writer($format)->download(
            $this->exports->build($edition, $dataset, $request->criteria()),
            $this->exports->filename($edition, $dataset, $format),
        );
    }

    private function writer(ExportFormat $format): SpreadsheetWriter
    {
        return match ($format) {
            ExportFormat::Csv => app(CsvWriter::class),
            ExportFormat::Xlsx => app(XlsxWriter::class),
        };
    }
}
