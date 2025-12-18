<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

interface BackendUserProviderInterface
{
    /**
     * @return int[]
     */
    public function getUserGroupIds(): array;

    public function isAdmin(): bool;

    public function getWorkspaceId(): int;

    public function isAuthenticated(): bool;

    public function getBackendUser(): ?BackendUserAuthentication;
}
