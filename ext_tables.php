<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Preload JavaScript modules to register them in the browser's import map
// and patch AjaxDataHandler to intercept all page delete commands
$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
$pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/custom-delete-handler.js');
$pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/ajax-data-handler-interceptor.js');
