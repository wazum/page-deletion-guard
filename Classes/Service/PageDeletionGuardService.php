<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;

final readonly class PageDeletionGuardService
{
    public function __construct(
        private BackendUserProviderInterface $userProvider,
        private ConnectionPool $connectionPool,
    ) {
    }

    public function shouldBypass(Settings $settings): bool
    {
        return !$settings->enabled || $this->userMayBypass($settings);
    }

    /**
     * @throws Exception
     */
    public function getChildCount(int $pageId, Settings $settings): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new DeletedRestriction());

        if ($settings->respectWorkspaces) {
            $workspaceId = $this->getBackendUser()?->workspace ?? 0;
            $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($workspaceId));
        }

        return (int) $queryBuilder
            ->count('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)))
            ->executeQuery()
            ->fetchOne();
    }

    public function isUserAllowedToDeleteWithChildren(Settings $settings): bool
    {
        return $this->userMayBypass($settings);
    }

    private function userMayBypass(Settings $settings): bool
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser) {
            return false;
        }

        if ($settings->allowAdminBypass && $backendUser->isAdmin()) {
            return true;
        }

        $userGroupIds = array_map('intval', $this->userProvider->getUserGroupIds());

        return [] !== array_intersect($settings->bypassGroupIds, $userGroupIds);
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
