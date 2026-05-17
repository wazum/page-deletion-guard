<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;

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
     * Counts every descendant of the given page in a single recursive CTE.
     * TYPO3 recursively deletes the entire subtree, so the warning must
     * reflect the full impact, not just direct children. The workspace
     * filter mirrors WorkspaceRestriction's "live or current workspace"
     * predicate; deeper version-state semantics are intentionally omitted
     * here because this is only a count for a warning dialog.
     *
     * @throws Exception
     */
    public function getChildCount(int $pageId, Settings $settings): int
    {
        $whereParts = ['deleted = 0'];
        $aliasedWhereParts = ['p.deleted = 0'];
        $params = ['rootPid' => $pageId];
        $types = ['rootPid' => ParameterType::INTEGER];

        if ($settings->respectWorkspaces) {
            $whereParts[] = 't3ver_wsid IN (0, :workspaceId)';
            $aliasedWhereParts[] = 'p.t3ver_wsid IN (0, :workspaceId)';
            $params['workspaceId'] = $this->userProvider->getWorkspaceId();
            $types['workspaceId'] = ParameterType::INTEGER;
        }

        $seedWhere = implode(' AND ', $whereParts);
        $recursiveWhere = implode(' AND ', $aliasedWhereParts);

        $sql = <<<SQL
            WITH RECURSIVE descendants(uid) AS (
                SELECT uid FROM pages WHERE pid = :rootPid AND {$seedWhere}
                UNION ALL
                SELECT p.uid FROM pages p
                INNER JOIN descendants d ON p.pid = d.uid
                WHERE {$recursiveWhere}
            )
            SELECT COUNT(*) FROM descendants
            SQL;

        return (int) $this->connectionPool
            ->getConnectionForTable('pages')
            ->executeQuery($sql, $params, $types)
            ->fetchOne();
    }

    private function userMayBypass(Settings $settings): bool
    {
        if (!$this->userProvider->isAuthenticated()) {
            return false;
        }

        if ($settings->allowAdminBypass && $this->userProvider->isAdmin()) {
            return true;
        }

        return [] !== array_intersect($settings->bypassGroupIds, $this->userProvider->getUserGroupIds());
    }
}
