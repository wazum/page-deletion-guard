<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

final readonly class Settings
{
    /**
     * @param int[] $bypassGroupIds
     */
    public function __construct(
        public bool $enabled = true,
        public bool $allowAdminBypass = true,
        public array $bypassGroupIds = [],
        public bool $respectWorkspaces = true,
    ) {
    }
}
