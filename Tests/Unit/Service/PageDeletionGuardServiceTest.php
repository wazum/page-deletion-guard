<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Service;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\Settings;

final class PageDeletionGuardServiceTest extends TestCase
{
    #[Test]
    public function shouldBypassWhenGuardIsDisabled(): void
    {
        $settings = new Settings(enabled: false, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);
        $service = $this->createService();

        self::assertTrue($service->shouldBypass($settings), 'Guard should be bypassed when disabled');
    }

    #[Test]
    public function shouldBypassWhenAdminBypassIsEnabled(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);
        $service = $this->createService(isAdmin: true);

        self::assertTrue($service->shouldBypass($settings), 'Admin should bypass when allowAdminBypass is enabled');
    }

    #[Test]
    public function shouldBypassWhenUserIsInBypassGroup(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: false, bypassGroupIds: [5, 10], respectWorkspaces: true);
        $service = $this->createService(userGroupIds: [2, 5]);

        self::assertTrue($service->shouldBypass($settings), 'User in bypass group should bypass guard');
    }

    #[Test]
    public function getChildCountReturnsDescendantCountFromCte(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: false);
        $service = $this->createService(childCount: 5);

        self::assertSame(5, $service->getChildCount(123, $settings));
    }

    #[Test]
    public function getChildCountIssuesSingleRecursiveCteQuery(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: false);

        $capturedSql = null;
        $service = $this->createServiceCapturingSql(7, $capturedSql);

        self::assertSame(7, $service->getChildCount(123, $settings));
        self::assertIsString($capturedSql);
        self::assertStringContainsString('WITH RECURSIVE', $capturedSql);
        self::assertStringContainsString('UNION ALL', $capturedSql);
    }

    #[Test]
    public function getChildCountAddsWorkspaceClauseWhenRespectWorkspacesEnabled(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);

        $capturedSql = null;
        $capturedParams = null;
        $service = $this->createServiceCapturingSql(3, $capturedSql, $capturedParams, workspaceId: 2);

        self::assertSame(3, $service->getChildCount(123, $settings));
        self::assertIsString($capturedSql);
        self::assertStringContainsString('t3ver_wsid', $capturedSql);
        self::assertIsArray($capturedParams);
        self::assertSame(2, $capturedParams['workspaceId'] ?? null);
    }

    #[Test]
    public function getChildCountOmitsWorkspaceClauseWhenRespectWorkspacesDisabled(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: false);

        $capturedSql = null;
        $service = $this->createServiceCapturingSql(0, $capturedSql);

        $service->getChildCount(123, $settings);
        self::assertIsString($capturedSql);
        self::assertStringNotContainsString('t3ver_wsid', $capturedSql);
    }

    #[Test]
    public function isUserAllowedToDeleteWithChildrenReturnsTrueForAdminWithBypass(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);
        $service = $this->createService(isAdmin: true);

        self::assertTrue($service->isUserAllowedToDeleteWithChildren($settings));
    }

    #[Test]
    public function isUserAllowedToDeleteWithChildrenReturnsTrueForUserInBypassGroup(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: false, bypassGroupIds: [5], respectWorkspaces: true);
        $service = $this->createService(userGroupIds: [2, 5]);

        self::assertTrue($service->isUserAllowedToDeleteWithChildren($settings));
    }

    #[Test]
    public function shouldNotBypassWhenUserIsNotAuthenticated(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);
        $service = $this->createService(isAuthenticated: false);

        self::assertFalse($service->shouldBypass($settings), 'Unauthenticated user should not bypass guard');
    }

    private function createService(
        array $userGroupIds = [],
        int $childCount = 0,
        bool $isAdmin = false,
        int $workspaceId = 0,
        bool $isAuthenticated = true,
    ): PageDeletionGuardService {
        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn($userGroupIds);
        $userProvider->method('isAdmin')->willReturn($isAdmin);
        $userProvider->method('getWorkspaceId')->willReturn($workspaceId);
        $userProvider->method('isAuthenticated')->willReturn($isAuthenticated);
        $userProvider->method('getBackendUser')->willReturn(null);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($this->createConnectionReturning($childCount));

        return new PageDeletionGuardService($userProvider, $connectionPool);
    }

    private function createServiceCapturingSql(
        int $childCount,
        ?string &$capturedSql,
        ?array &$capturedParams = null,
        int $workspaceId = 0,
    ): PageDeletionGuardService {
        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn([]);
        $userProvider->method('isAdmin')->willReturn(false);
        $userProvider->method('getWorkspaceId')->willReturn($workspaceId);
        $userProvider->method('isAuthenticated')->willReturn(true);
        $userProvider->method('getBackendUser')->willReturn(null);

        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn($childCount);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturnCallback(
            static function (string $sql, array $params = [], array $types = []) use (&$capturedSql, &$capturedParams, $result) {
                $capturedSql = $sql;
                $capturedParams = $params;

                return $result;
            }
        );

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        return new PageDeletionGuardService($userProvider, $connectionPool);
    }

    private function createConnectionReturning(int $childCount): Connection
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn($childCount);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        return $connection;
    }
}
