<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Service;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
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
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = $this->createService();

        self::assertTrue($service->shouldBypass($settings), 'Admin should bypass when allowAdminBypass is enabled');

        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function shouldBypassWhenUserIsInBypassGroup(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: false, bypassGroupIds: [5, 10], respectWorkspaces: true);
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = $this->createService(userGroupIds: [2, 5]);

        self::assertTrue($service->shouldBypass($settings), 'User in bypass group should bypass guard');

        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function getChildCountReturnsCorrectCount(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: false);
        $service = $this->createService(childCount: 5);

        $count = $service->getChildCount(123, $settings);

        self::assertSame(5, $count, 'Should return correct child count');
    }

    #[Test]
    public function getChildCountPassesWorkspaceIdToRestrictionWhenRespectWorkspacesEnabled(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->workspace = 2;
        $GLOBALS['BE_USER'] = $backendUser;

        $restrictionContainer = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictionContainer->expects(self::once())->method('removeAll')->willReturnSelf();
        $restrictionContainer->expects(self::exactly(2))->method('add')->willReturnSelf();

        $service = $this->createServiceWithRestrictionContainer($restrictionContainer, 3);

        $count = $service->getChildCount(123, $settings);

        self::assertSame(3, $count);

        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function isUserAllowedToDeleteWithChildrenReturnsTrueForAdminWithBypass(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: true, bypassGroupIds: [], respectWorkspaces: true);
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = $this->createService();

        self::assertTrue($service->isUserAllowedToDeleteWithChildren($settings));

        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function isUserAllowedToDeleteWithChildrenReturnsTrueForUserInBypassGroup(): void
    {
        $settings = new Settings(enabled: true, allowAdminBypass: false, bypassGroupIds: [5], respectWorkspaces: true);
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->userGroupsUID = [2, 5];
        $GLOBALS['BE_USER'] = $backendUser;

        $service = $this->createService(userGroupIds: [2, 5]);

        self::assertTrue($service->isUserAllowedToDeleteWithChildren($settings));

        unset($GLOBALS['BE_USER']);
    }

    private function createService(array $userGroupIds = [], int $childCount = 0): PageDeletionGuardService
    {
        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn($userGroupIds);

        $queryBuilder = $this->createMockQueryBuilder($childCount);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return new PageDeletionGuardService($userProvider, $connectionPool);
    }

    private function createServiceWithRestrictionContainer(QueryRestrictionContainerInterface $restrictionContainer, int $childCount): PageDeletionGuardService
    {
        $userProvider = $this->createMock(BackendUserProviderInterface::class);

        $statement = $this->createMock(Result::class);
        $statement->method('fetchOne')->willReturn($childCount);

        $expr = $this->createMock(ExpressionBuilder::class);
        $expr->method('eq')->willReturn('pid = 1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(static fn ($value) => (string) $value);
        $queryBuilder->method('executeQuery')->willReturn($statement);
        $queryBuilder->method('getRestrictions')->willReturn($restrictionContainer);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return new PageDeletionGuardService($userProvider, $connectionPool);
    }

    private function createMockQueryBuilder(int $childCount): QueryBuilder
    {
        $statement = $this->createMock(Result::class);
        $statement->method('fetchOne')->willReturn($childCount);

        $expr = $this->createMock(ExpressionBuilder::class);
        $expr->method('eq')->willReturn('pid = 1');

        $restrictionContainer = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictionContainer->method('removeAll')->willReturnSelf();
        $restrictionContainer->method('add')->willReturnSelf();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(static fn ($value) => (string) $value);
        $queryBuilder->method('executeQuery')->willReturn($statement);
        $queryBuilder->method('getRestrictions')->willReturn($restrictionContainer);

        return $queryBuilder;
    }
}
