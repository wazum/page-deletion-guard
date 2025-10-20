<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Deletion Guard',
    'description' => 'Prevents deletion of pages with children',
    'category' => 'be',
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wolfgang@wazum.com',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.99.99',
            'php' => '8.2.0-8.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
