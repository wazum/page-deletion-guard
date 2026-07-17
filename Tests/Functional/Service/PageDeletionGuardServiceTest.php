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

    #[Test]
    public function countsAllDescendantsAcrossMultipleLevels(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        self::assertSame(4, $this->createService()->getChildCount(1, new Settings()));
    }

    #[Test]
    public function returnsZeroForLeafPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        self::assertSame(0, $this->createService()->getChildCount(6, new Settings()));
    }

    #[Test]
    public function returnsZeroWhenOnlyDeletedChildrenExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        self::assertSame(0, $this->createService()->getChildCount(3, new Settings()));
    }

    #[Test]
    public function doesNotCountTranslatedPagesAsChildren(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages-with-translations.csv');

        self::assertSame(2, $this->createService()->getChildCount(1, new Settings()));
    }

    private function createService(): PageDeletionGuardService
    {
        return new PageDeletionGuardService(
            new BackendUserProvider(),
            $this->get(ConnectionPool::class)
        );
    }
}
