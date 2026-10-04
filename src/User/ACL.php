<?php

declare(strict_types=1);

namespace Src\User;

use Src\Organization\OrganizationFunctionRepository;
use Src\Organization\OrganizationMemberRepository;

final class ACL
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationFunctionRepository $organizationFunctionRepository,
        private OrganizationMemberRepository $organizationMemberRepository,
    ) {}

    public function isAdmin(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        return (bool)($user?->isAdmin ?? false);
    }

    public function isPermitted(int $userId, int $organizationId, Permission $permission): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        $member = $this->organizationMemberRepository->findByUserAndOrganization($userId, $organizationId);

        if (!$member) {
            return false;
        }

        $function = $this->organizationFunctionRepository->findById((int)$member->function);

        if (!$function) {
            return false;
        }

        return match ($permission) {
            Permission::SIGN => (bool)$function->maySign,
            Permission::MANAGE_ORGANIZATION => (bool)$function->mayManageOrganization,
            Permission::MANAGE_MEETINGS => (bool)$function->mayManageMeetings,
        };
    }
}

enum Permission: string
{
    case SIGN = 'sign';
    case MANAGE_ORGANIZATION = 'manage_organization';
    case MANAGE_MEETINGS = 'manage_meetings';
}
