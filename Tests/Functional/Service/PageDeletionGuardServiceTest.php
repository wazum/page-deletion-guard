<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\PageDeletionGuard\Service\BackendUserProvider;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\Settings;

final class PageDeletionGuardServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['wazum/page-deletion-guard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
    }

    #[Test]
    public function countsAllDescendantsAcrossMultipleLevels(): void
    {
        self::assertSame(4, $this->createService()->getChildCount(1, new Settings()));
    }

    #[Test]
    public function returnsZeroForLeafPage(): void
    {
        self::assertSame(0, $this->createService()->getChildCount(6, new Settings()));
    }

    #[Test]
    public function returnsZeroWhenOnlyDeletedChildrenExist(): void
    {
        self::assertSame(0, $this->createService()->getChildCount(3, new Settings()));
    }

    private function createService(): PageDeletionGuardService
    {
        return new PageDeletionGuardService(
            new BackendUserProvider(),
            $this->get(ConnectionPool::class)
        );
    }
}
