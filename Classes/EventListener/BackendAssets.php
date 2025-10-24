<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Page\PageRenderer;

final readonly class BackendAssets
{
    public function __construct(
        private PageRenderer $pageRenderer,
    ) {
    }

    /**
     * @psalm-suppress UnusedParam
     */
    public function __invoke(AfterBackendPageRenderEvent $event): void
    {
        $this->pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/custom-delete-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/ajax-data-handler-interceptor.js');

        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf',
            'js.',
            'js.'
        );
    }
}
