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

    /**
     * SQL fragment restricting a pages query to rows the current user may
     * access for the given permission bit, or a never-matching clause when
     * no backend user is available.
     */
    public function getPagePermissionClause(int $permission): string;

    public function getBackendUser(): ?BackendUserAuthentication;
}
