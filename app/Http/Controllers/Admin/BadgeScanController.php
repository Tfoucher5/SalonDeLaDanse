<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BadgeScanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanBadgeRequest;
use App\Models\Shift;
use App\Services\Badges\BadgePrinter;
use App\Services\Badges\BadgeScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Le verdict d'un badge pour la fenêtre de scan : du JSON, lu sans
 * rechargement de page. Contrôle visuel seulement, rien n'est enregistré.
 *
 * La réponse porte de quoi reconnaître la personne et l'orienter, jamais ses
 * coordonnées : la fenêtre s'affiche à l'entrée, sous les yeux de tous.
 */
class BadgeScanController extends Controller
{
    public function __construct(
        private readonly BadgeScanner $scanner,
        private readonly BadgePrinter $printer,
    ) {}

    public function __invoke(ScanBadgeRequest $request): JsonResponse
    {
        $scan = $this->scanner->scan($request->code());
        $volunteer = $scan->status === BadgeScanStatus::Unrecognized ? null : $scan->volunteer;

        return response()->json([
            'status' => $scan->status->value,
            'title' => $scan->status->title(),
            'message' => $scan->status->message(),
            'volunteer' => $volunteer === null ? null : [
                'name' => $volunteer->full_name,
                'initials' => $volunteer->initials,
                'identifier' => $scan->edition === null ? null : $this->printer->identifier($volunteer, $scan->edition),
                'edition' => $scan->edition?->name,
                'photo' => $this->printer->hasPhoto($volunteer) ? Storage::disk('public')->url($volunteer->photo_path) : null,
                'url' => route('admin.volunteers.show', $volunteer),
                'shifts' => $this->printer->assignedShifts($volunteer)->map(fn (Shift $shift): array => [
                    'day' => ucfirst($shift->date->translatedFormat('D j M')),
                    'time' => $shift->timeSlot->label(),
                    'mission' => $shift->mission->name,
                    'today' => $shift->date->isToday(),
                ])->all(),
            ],
        ]);
    }
}
