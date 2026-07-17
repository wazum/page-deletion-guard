<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\PageDeletionGuard\Service\BackendUserProvider;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
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

    #[Test]
    public function doesNotCountWorkspaceVersionsOfLivePagesTwice(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages-with-workspace.csv');

        // Child A (live) counted once, its workspace version ignored, the
        // new-in-workspace page counted once.
        self::assertSame(2, $this->createService(workspaceId: 1)->getChildCount(1, new Settings()));
    }

    private function createService(int $workspaceId = 0): PageDeletionGuardService
    {
        $userProvider = 0 === $workspaceId
            ? new BackendUserProvider()
            : $this->createUserProviderForWorkspace($workspaceId);

        return new PageDeletionGuardService(
            $userProvider,
            $this->get(ConnectionPool::class)
        );
    }

    private function createUserProviderForWorkspace(int $workspaceId): BackendUserProviderInterface
    {
        return new class($workspaceId) implements BackendUserProviderInterface {
            public function __construct(private readonly int $workspaceId)
            {
            }

            public function getUserGroupIds(): array
            {
                return [];
            }

            public function isAdmin(): bool
            {
                return false;
            }

            public function getWorkspaceId(): int
            {
                return $this->workspaceId;
            }

            public function isAuthenticated(): bool
            {
                return true;
            }

            public function getPagePermissionClause(int $permission): string
            {
                return '1=1';
            }

            public function getBackendUser(): ?BackendUserAuthentication
            {
                return null;
            }
        };
    }
}
