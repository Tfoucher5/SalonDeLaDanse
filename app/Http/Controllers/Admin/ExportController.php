<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExportDataset;
use App\Enums\ExportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VolunteerSearchRequest;
use App\Models\Edition;
use App\Services\Export\CsvWriter;
use App\Services\Export\EditionExports;
use App\Services\Export\SpreadsheetWriter;
use App\Services\Export\XlsxWriter;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * La sortie des données : trois feuilles, deux formats.
 *
 * Les critères sont exactement ceux de la recherche de bénévoles — même
 * `FormRequest`, même requête derrière. Ce qu'on voit à l'écran est ce qu'on
 * télécharge, sinon l'export devient un piège.
 */
class ExportController extends Controller
{
    public function __construct(private readonly EditionExports $exports) {}

    /**
     * L'écran de sélection : les critères, puis un bouton par format.
     */
    public function index(VolunteerSearchRequest $request): View
    {
        $edition = Edition::current();
        $criteria = $request->criteria();

        return view('admin.exports.index', [
            'edition' => $edition,
            'criteria' => $criteria,
            'missions' => $edition?->missions()->get() ?? collect(),
            'days' => $edition?->days() ?? collect(),
            'datasets' => ExportDataset::cases(),
            'formats' => ExportFormat::cases(),
        ]);
    }

    /**
     * Le fichier lui-même. Les énumérations sont liées par la route : un
     * format ou une feuille inconnus rendent un 404, pas une page blanche.
     */
    public function download(
        VolunteerSearchRequest $request,
        ExportDataset $dataset,
        ExportFormat $format,
    ): Response {
        $edition = Edition::current();

        abort_if($edition === null, 404, "Aucune édition n'est ouverte : il n'y a rien à exporter.");

        $sheet = $this->exports->build($edition, $dataset, $request->criteria());

        return $this->writer($format)->download(
            $sheet,
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
