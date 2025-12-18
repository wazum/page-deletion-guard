<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;

final readonly class QueryRestrictionFactory implements QueryRestrictionFactoryInterface
{
    public function __construct(
        private DeletedRestriction $deletedRestriction,
    ) {
    }

    public function createDeletedRestriction(): QueryRestrictionInterface
    {
        return $this->deletedRestriction;
    }

    public function createWorkspaceRestriction(int $workspaceId): QueryRestrictionInterface
    {
        return new WorkspaceRestriction($workspaceId);
    }
}
