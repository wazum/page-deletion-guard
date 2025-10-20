<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

final readonly class BackendUserProvider implements BackendUserProviderInterface
{
    public function getUserGroupIds(): array
    {
        $backendUser = $this->getBackendUser();
        $groupIds = is_array($backendUser?->userGroupsUID) ? $backendUser->userGroupsUID : [];

        return array_map('intval', $groupIds);
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
