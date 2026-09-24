<?php

namespace App\Services\Badges;

use App\Enums\BadgeSelection;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use App\Services\VolunteerSearch;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Les badges bénévoles : leur contenu, leur mise en page et leur vérification.
 *
 * Un badge porte un QR code vers une page publique signée. La signature
 * interdit de forger l'adresse d'un autre bénévole en changeant l'identifiant ;
 * l'état affiché, lui, est lu au moment du scan : un compte retiré de
 * l'édition cesse d'être reconnu sans qu'il faille réimprimer quoi que ce soit.
 */
class BadgePrinter
{
    /**
     * La planche A4 : quatre badges portrait de 95 × 135 mm, sur deux colonnes
     * et deux rangées, centrés pour laisser la place aux traits de coupe dans
     * les marges.
     */
    public const array SHEET = [
        'page_width' => 210.0,
        'page_height' => 297.0,
        'badge_width' => 95.0,
        'badge_height' => 135.0,
        'columns' => 2,
        'rows' => 2,
    ];

    /**
     * Le portrait, recadré en carré à la taille d'impression : 60 mm de côté
     * à 270 dpi. La vue le découpe en cercle. Au-delà, le PDF grossirait sans
     * rien gagner à l'œil.
     */
    private const int PORTRAIT_WIDTH = 640;

    private const int PORTRAIT_HEIGHT = 640;

    public function __construct(private readonly VolunteerSearch $search) {}

    /**
     * L'identifiant lisible imprimé sur le badge : `SDD27-0042`.
     *
     * Dérivé de l'édition et de l'identifiant du compte, il ne demande aucune
     * colonne : il reste stable tant que le compte existe.
     */
    public function identifier(User $volunteer, Edition $edition): string
    {
        return sprintf('SDD%s-%04d', $edition->starts_on->format('y'), $volunteer->getKey());
    }

    /**
     * L'adresse encodée dans le QR code, signée et sans expiration.
     */
    public function verificationUrl(User $volunteer, Edition $edition): string
    {
        return URL::signedRoute('badges.verify', [
            'edition' => $edition->getKey(),
            'volunteer' => $volunteer->getKey(),
        ]);
    }

    /**
     * Le compte est-il, au moment où on le demande, un bénévole de cette édition ?
     */
    public function isActiveVolunteer(User $volunteer, Edition $edition): bool
    {
        return $volunteer->isVolunteer()
            && $edition->is_active
            && $volunteer->activeEdition()?->is($edition) === true;
    }

    /**
     * Les créneaux du bénévole, dans l'ordre de la journée, pour le contrôle
     * à l'entrée : l'équipe voit d'un coup d'œil où la personne est attendue.
     *
     * @return Collection<int, Shift>
     */
    public function assignedShifts(User $volunteer): Collection
    {
        return $volunteer->shifts()
            ->with(['mission:id,name', 'timeSlot:id,starts_at,ends_at,position'])
            ->get()
            ->sortBy(fn (Shift $shift): string => $shift->date->toDateString()
                .'#'.str_pad((string) $shift->timeSlot->position, 2, '0', STR_PAD_LEFT))
            ->values();
    }

    /**
     * La photo existe-t-elle vraiment sur le disque ?
     *
     * Un chemin en base sans fichier derrière donnerait un badge sans photo :
     * l'administrateur doit le voir avant d'imprimer, comme une photo absente.
     */
    public function hasPhoto(User $volunteer): bool
    {
        return filled($volunteer->photo_path)
            && Storage::disk('public')->exists($volunteer->photo_path);
    }

    /**
     * Les bénévoles de l'édition dont le badge portera les initiales.
     *
     * @return Collection<int, User>
     */
    public function volunteersWithoutPhoto(Edition $edition): Collection
    {
        return User::query()
            ->volunteers()
            ->ofEdition($edition)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'photo_path', 'role', 'edition_id'])
            ->reject(fn (User $volunteer): bool => $this->hasPhoto($volunteer))
            ->values();
    }

    /**
     * Les bénévoles d'une génération groupée, dans l'ordre alphabétique.
     *
     * Le mode « filtres » rejoue la recherche du back-office : ce qu'on voit à
     * l'écran est ce qu'on imprime. Le mode « sélection » reste borné à
     * l'édition, quels que soient les identifiants reçus.
     *
     * @param  array{name?: ?string, mission?: ?int, status?: ?string, day?: ?string}  $criteria
     * @param  array<int, int>  $volunteerIds
     * @return Collection<int, User>
     */
    public function volunteers(Edition $edition, BadgeSelection $selection, array $criteria, array $volunteerIds): Collection
    {
        return match ($selection) {
            BadgeSelection::Filters => $this->search->query($edition, $criteria)->get(),
            BadgeSelection::Selection => User::query()
                ->volunteers()
                ->ofEdition($edition)
                ->whereKey($volunteerIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        };
    }

    public function badge(User $volunteer, Edition $edition): Badge
    {
        $verificationUrl = $this->verificationUrl($volunteer, $edition);

        return new Badge(
            volunteerId: $volunteer->getKey(),
            identifier: $this->identifier($volunteer, $edition),
            firstName: (string) $volunteer->first_name,
            lastName: (string) $volunteer->last_name,
            initials: $volunteer->initials,
            editionName: $edition->name,
            verificationUrl: $verificationUrl,
            qrCode: $this->qrCode($verificationUrl),
            photo: $this->hasPhoto($volunteer) ? $this->portrait($volunteer->photo_path) : null,
        );
    }

    /**
     * Le PDF A4 prêt à imprimer, un ou plusieurs badges par planche.
     *
     * @param  Collection<int, User>  $volunteers
     */
    public function pdf(Collection $volunteers, Edition $edition): PdfDocument
    {
        $perSheet = self::SHEET['columns'] * self::SHEET['rows'];

        return Pdf::loadView('admin.badges.pdf', [
            'edition' => $edition,
            'sheets' => $volunteers
                ->map(fn (User $volunteer): Badge => $this->badge($volunteer, $edition))
                ->chunk($perSheet),
            'layout' => $this->layout(),
            'fonts' => resource_path('fonts/plus-jakarta-sans'),
        ])->setPaper('a4');
    }

    public function filename(Collection $volunteers, Edition $edition): string
    {
        if ($volunteers->count() === 1) {
            return 'badge-'.$this->identifier($volunteers->first(), $edition).'.pdf';
        }

        return sprintf('badges-SDD%s.pdf', $edition->starts_on->format('y'));
    }

    /**
     * La position de chaque badge sur la planche, en millimètres.
     *
     * @return array{margin_x: float, margin_y: float, badge_width: float, badge_height: float, cells: array<int, array{left: float, top: float}>, cuts_x: array<int, float>, cuts_y: array<int, float>}
     */
    private function layout(): array
    {
        $sheet = self::SHEET;
        $marginX = ($sheet['page_width'] - $sheet['columns'] * $sheet['badge_width']) / 2;
        $marginY = ($sheet['page_height'] - $sheet['rows'] * $sheet['badge_height']) / 2;

        $cells = [];

        for ($row = 0; $row < $sheet['rows']; $row++) {
            for ($column = 0; $column < $sheet['columns']; $column++) {
                $cells[] = [
                    'left' => $marginX + $column * $sheet['badge_width'],
                    'top' => $marginY + $row * $sheet['badge_height'],
                ];
            }
        }

        return [
            'margin_x' => $marginX,
            'margin_y' => $marginY,
            'badge_width' => $sheet['badge_width'],
            'badge_height' => $sheet['badge_height'],
            'cells' => $cells,
            'cuts_x' => array_map(fn (int $line): float => $marginX + $line * $sheet['badge_width'], range(0, $sheet['columns'])),
            'cuts_y' => array_map(fn (int $line): float => $marginY + $line * $sheet['badge_height'], range(0, $sheet['rows'])),
        ];
    }

    /**
     * Le QR code en SVG : aucune extension d'image n'est requise pour le
     * produire, et dompdf le trace en vectoriel, net à toute taille.
     */
    private function qrCode(string $url): string
    {
        $style = new RendererStyle(240, 2, null, null, Fill::uniformColor(
            new Rgb(255, 255, 255),
            new Rgb(31, 26, 28),
        ));

        $svg = (new Writer(new ImageRenderer($style, new SvgImageBackEnd)))
            ->writeString($url, ecLevel: ErrorCorrectionLevel::M());

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * La photo recadrée en carré, en JPEG, prête à être découpée en cercle.
     *
     * dompdf n'applique pas `object-fit` : sans ce recadrage, une photo
     * paysage serait écrasée. Le recadrage retire aussi la transparence d'un
     * PNG, posé sur fond blanc, et ramène une photo de plusieurs mégaoctets à
     * quelques dizaines de kilooctets par badge.
     *
     * GD est indispensable : sans lui, dompdf refuse tout PNG et tout WebP.
     * Mieux vaut une erreur qui le dit qu'une planche de badges sans visages.
     */
    private function portrait(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('L\'extension PHP GD est requise pour imprimer les photos des badges : activez `extension=gd` dans php.ini.');
        }

        $source = @imagecreatefromstring((string) Storage::disk('public')->get($path));

        // Un fichier illisible ne bloque pas toute la planche : ce badge
        // retombe sur les initiales, comme un badge sans photo.
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = self::PORTRAIT_WIDTH / self::PORTRAIT_HEIGHT;

        if ($width / $height > $ratio) {
            $cropHeight = $height;
            $cropWidth = max(1, (int) round($height * $ratio));
            $cropX = intdiv($width - $cropWidth, 2);
            $cropY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = max(1, (int) round($width / $ratio));
            $cropX = 0;
            // Le visage se trouve plus souvent dans le haut d'un portrait : on
            // retire deux fois plus sous le menton qu'au-dessus de la tête.
            $cropY = intdiv($height - $cropHeight, 3);
        }

        $portrait = imagecreatetruecolor(self::PORTRAIT_WIDTH, self::PORTRAIT_HEIGHT);
        imagefill($portrait, 0, 0, imagecolorallocate($portrait, 255, 255, 255));
        imagecopyresampled(
            $portrait, $source,
            0, 0, $cropX, $cropY,
            self::PORTRAIT_WIDTH, self::PORTRAIT_HEIGHT, $cropWidth, $cropHeight,
        );

        ob_start();
        imagejpeg($portrait, null, 85);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }
}
