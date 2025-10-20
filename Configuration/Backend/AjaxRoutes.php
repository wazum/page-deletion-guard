<?php

declare(strict_types=1);

use Wazum\PageDeletionGuard\Controller\ChildPageCheckController;

return [
    'page_deletion_guard_check_children' => [
        'path' => '/page-deletion-guard/check-children',
        'target' => ChildPageCheckController::class . '::checkChildrenAction',
        'methods' => ['GET'],
    ],
];
