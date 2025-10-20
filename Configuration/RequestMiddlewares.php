<?php

declare(strict_types=1);

return [
    'backend' => [
        'wazum/page-deletion-guard/backend-javascript' => [
            'target' => Wazum\PageDeletionGuard\Middleware\BackendJavaScriptMiddleware::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
            'before' => [
                'typo3/cms-backend/output-compression',
            ],
        ],
    ],
];
