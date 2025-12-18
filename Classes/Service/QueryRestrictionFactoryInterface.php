<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

interface QueryRestrictionFactoryInterface
{
    public function createDeletedRestriction(): QueryRestrictionInterface;

    public function createWorkspaceRestriction(int $workspaceId): QueryRestrictionInterface;
}
