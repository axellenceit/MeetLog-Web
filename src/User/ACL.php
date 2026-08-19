<?php

declare(strict_types=1);

namespace Src\User;

use Src\Organisation\OrganisationsFunktionRepository;
use Src\Organisation\OrganisationsMitgliedRepository;

final class ACL
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganisationsFunktionRepository $organisationsFunktionRepository,
        private OrganisationsMitgliedRepository $organisationsMitgliedRepository,
    ) {}

    public function isAdmin(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        return (bool)($user?->istAdmin ?? false);
    }

    public function isPermitted(int $userId, int $organisationId, Permission $permission): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        $mitglied = $this->organisationsMitgliedRepository->findByUserAndOrganisation($userId, $organisationId);

        if (!$mitglied) {
            return false;
        }

        $funktion = $this->organisationsFunktionRepository->findById((int)$mitglied->funktion);

        if (!$funktion) {
            return false;
        }

        return match ($permission) {
            Permission::SIGN => (bool)$funktion->darfUnterschreiben,
            Permission::MANAGE_ORGANISATION => (bool)$funktion->darfOrganisationVerwalten,
            Permission::MANAGE_MEETINGS => (bool)$funktion->darfBesprechungenVerwalten,
        };
    }
}

enum Permission: string
{
    case SIGN = 'sign';
    case MANAGE_ORGANISATION = 'manage_organisation';
    case MANAGE_MEETINGS = 'manage_meetings';
}
