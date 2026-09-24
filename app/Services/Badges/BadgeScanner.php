<?php

namespace App\Services\Badges;

use App\Enums\BadgeScanStatus;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Le contrôle d'un badge depuis le scanner du back-office.
 *
 * Le scanner lit le contenu brut du QR code : l'adresse signée de la page de
 * vérification. Elle est contrôlée exactement comme si on l'ouvrait, signature
 * d'abord, sans passer par le navigateur. Quand la caméra est indisponible,
 * l'identifiant lisible du badge (`SDD27-0042`) se saisit à la main : il n'a
 * pas de signature, mais seul un administrateur connecté y a accès.
 */
class BadgeScanner
{
    private const string IDENTIFIER_PATTERN = '/^SDD(\d{2})-(\d{1,10})$/i';

    public function __construct(
        private readonly BadgePrinter $printer,
        private readonly Router $router,
    ) {}

    public function scan(string $code): BadgeScan
    {
        $code = trim($code);

        if (preg_match(self::IDENTIFIER_PATTERN, $code, $matches) === 1) {
            return $this->fromIdentifier($matches[1], (int) $matches[2]);
        }

        return $this->fromVerificationUrl($code);
    }

    /**
     * Un identifiant saisi : il vise l'édition courante, dont il doit porter
     * l'année. Celui d'une édition passée désigne un badge qui ne vaut plus.
     */
    private function fromIdentifier(string $year, int $volunteerId): BadgeScan
    {
        $edition = Edition::current();
        $volunteer = User::query()->find($volunteerId);

        if ($edition === null || $volunteer === null) {
            return new BadgeScan(BadgeScanStatus::Unrecognized);
        }

        if ($edition->starts_on->format('y') !== $year) {
            return new BadgeScan(BadgeScanStatus::Inactive, $volunteer, $edition);
        }

        return $this->verdict($volunteer, $edition);
    }

    /**
     * L'adresse lue dans le QR code. Une signature fausse, une adresse qui ne
     * mène pas à la vérification d'un badge : le code n'est pas du Salon.
     */
    private function fromVerificationUrl(string $url): BadgeScan
    {
        $request = Request::create($url);

        if (! URL::hasValidSignature($request)) {
            return new BadgeScan(BadgeScanStatus::Unrecognized);
        }

        try {
            $route = $this->router->getRoutes()->match($request);
        } catch (HttpException) {
            return new BadgeScan(BadgeScanStatus::Unrecognized);
        }

        if ($route->getName() !== 'badges.verify') {
            return new BadgeScan(BadgeScanStatus::Unrecognized);
        }

        $edition = Edition::query()->find((int) $route->parameter('edition'));
        $volunteer = User::query()->find((int) $route->parameter('volunteer'));

        if ($edition === null || $volunteer === null) {
            return new BadgeScan(BadgeScanStatus::NoHolder, $volunteer, $edition);
        }

        return $this->verdict($volunteer, $edition);
    }

    private function verdict(User $volunteer, Edition $edition): BadgeScan
    {
        $status = $this->printer->isActiveVolunteer($volunteer, $edition)
            ? BadgeScanStatus::Valid
            : BadgeScanStatus::Inactive;

        return new BadgeScan($status, $volunteer, $edition);
    }
}
