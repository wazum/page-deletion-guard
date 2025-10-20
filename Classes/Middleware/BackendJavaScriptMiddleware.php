<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;

final readonly class BackendJavaScriptMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PageRenderer $pageRenderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (ApplicationType::fromRequest($request)->isBackend()) {
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@wazum/page-deletion-guard/ajax-data-handler-interceptor.js')
            );

            $this->pageRenderer->addInlineLanguageLabelFile(
                'EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf',
                'js.',
                'js.'
            );
        }

        return $handler->handle($request);
    }
}
