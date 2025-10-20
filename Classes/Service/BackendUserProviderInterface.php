<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

interface BackendUserProviderInterface
{
    /**
     * @return int[]
     */
    public function getUserGroupIds(): array;
}
