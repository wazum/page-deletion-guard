<?php

declare(strict_types=1);

use Wazum\PageDeletionGuard\Hook\PageDeletionGuardHook;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['page_deletion_guard']
    = PageDeletionGuardHook::class;
