<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewInterface;
use Wazum\PageDeletionGuard\EventListener\BackendAssets;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final class BackendAssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function doesNotLoadAssetsWhenGuardIsDisabled(): void
    {
        $this->givenRequestPath('/typo3/module/web/list');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        $listener = new BackendAssets($pageRenderer, $this->settingsFactory(enabled: false));
        $listener($this->event());
    }

    #[Test]
    public function loadsAssetsWhenGuardIsEnabledOnADeleteCapableRoute(): void
    {
        $this->givenRequestPath('/typo3/module/web/list');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::exactly(2))->method('loadJavaScriptModule');

        $listener = new BackendAssets($pageRenderer, $this->settingsFactory(enabled: true));
        $listener($this->event());
    }

    private function givenRequestPath(string $path): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function event(): AfterBackendPageRenderEvent
    {
        return new AfterBackendPageRenderEvent('', $this->createMock(ViewInterface::class));
    }

    private function settingsFactory(bool $enabled): SettingsFactory
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(['enabled' => $enabled]);

        return new SettingsFactory($extensionConfiguration);
    }
}
