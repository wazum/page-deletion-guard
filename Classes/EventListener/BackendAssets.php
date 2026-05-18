<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;

#[AsEventListener(identifier: 'page-deletion-guard/backend-assets')]
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
        if (!$this->requestNeedsAssets()) {
            return;
        }

        $this->pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/custom-delete-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@wazum/page-deletion-guard/ajax-data-handler-interceptor.js');

        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf',
            'js.',
            'js.'
        );
    }

    private function requestNeedsAssets(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            // When we cannot tell, keep the existing behavior and load.
            return true;
        }

        $path = $request->getUri()->getPath();

        // Backend entry (page tree may be shown next).
        if ('/typo3' === $path || '/typo3/' === $path || '/typo3/main' === $path) {
            return true;
        }

        // Modules where a page deletion can be triggered (page tree context menu,
        // list module, records module on v14).
        return str_starts_with($path, '/typo3/module/web/')
            || str_starts_with($path, '/typo3/module/content/');
    }
}
