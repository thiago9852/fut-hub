<?php

namespace App\Service\Security;

use App\Entity\Organization;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Resolves the Organization the currently authenticated user belongs to.
 *
 * Every organization-scoped query/service in the application should read
 * the current organization through this service instead of trusting
 * client input, so that one organization can never see another's data.
 */
class OrganizationContext
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getCurrentOrganization(): ?Organization
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user->getOrganization();
    }

    public function requireCurrentOrganization(): Organization
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization instanceof Organization) {
            throw new \RuntimeException('Nenhuma organização associada ao usuário autenticado.');
        }

        return $organization;
    }
}
