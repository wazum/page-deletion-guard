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

    public function isAdmin(): bool
    {
        return $this->getBackendUser()?->isAdmin() ?? false;
    }

    public function getWorkspaceId(): int
    {
        return $this->getBackendUser()?->workspace ?? 0;
    }

    public function isAuthenticated(): bool
    {
        return null !== $this->getBackendUser();
    }

    public function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
