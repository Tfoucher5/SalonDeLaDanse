<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExportDataset;
use App\Enums\ExportFormat;
use Illuminate\Validation\Rule;

/**
 * Un téléchargement depuis la fenêtre d'export.
 *
 * Les critères sont ceux de la recherche, hérités tels quels : la fenêtre les
 * reprend de l'écran qu'on regarde. S'y ajoutent la feuille et le format,
 * portés par le formulaire lui-même.
 */
class ExportRequest extends VolunteerSearchRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'dataset' => ['required', Rule::enum(ExportDataset::class)],
            'format' => ['required', Rule::enum(ExportFormat::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'dataset.*' => 'Choisissez une feuille à exporter.',
            'format.*' => 'Ce format de fichier n\'existe pas.',
        ];
    }

    public function exportDataset(): ExportDataset
    {
        return $this->enum('dataset', ExportDataset::class);
    }

    public function exportFormat(): ExportFormat
    {
        return $this->enum('format', ExportFormat::class);
    }
}
