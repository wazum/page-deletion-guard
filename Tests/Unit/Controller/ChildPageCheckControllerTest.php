<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Controller;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use Wazum\PageDeletionGuard\Controller\ChildPageCheckController;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final class ChildPageCheckControllerTest extends TestCase
{
    #[Test]
    public function returnsAllowedWhenGuardDisabled(): void
    {
        $controller = $this->createController(enabled: false, childCount: 5);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['pageUid' => 123]);

        $response = $controller->checkChildrenAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);

        $content = json_decode($response->getBody()->getContents(), true);
        self::assertFalse($content['hasChildren'], 'Should report no children when guard disabled so JS falls back to TYPO3 default');
        self::assertSame(0, $content['childCount'], 'Child count should be 0 when guard disabled');
        self::assertTrue($content['isAllowed'], 'Should be allowed when guard disabled');
    }

    #[Test]
    public function returnsAllowedWhenUserIsAdmin(): void
    {
        $controller = $this->createController(allowAdminBypass: true, childCount: 5, isAdmin: true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['pageUid' => 123]);

        $response = $controller->checkChildrenAction($request);

        $content = json_decode($response->getBody()->getContents(), true);
        self::assertTrue($content['hasChildren'], 'Should report real children count even when admin can bypass');
        self::assertSame(5, $content['childCount'], 'Should return real child count even when admin can bypass');
        self::assertTrue($content['isAllowed'], 'Admin should be allowed when bypass enabled');
    }

    #[Test]
    public function returnsChildrenInfoWhenGuardEnabled(): void
    {
        $controller = $this->createController(childCount: 3);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['pageUid' => 123]);

        $response = $controller->checkChildrenAction($request);

        $content = json_decode($response->getBody()->getContents(), true);
        self::assertTrue($content['hasChildren'], 'Should report children when guard enabled');
        self::assertSame(3, $content['childCount'], 'Should return actual child count');
        self::assertFalse($content['isAllowed'], 'Regular user should not be allowed');
    }

    #[Test]
    public function returnsErrorOnInvalidPageUid(): void
    {
        $controller = $this->createController(enabled: true, childCount: 0);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['pageUid' => 0]);

        $response = $controller->checkChildrenAction($request);

        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function returnsErrorOnMissingPageUid(): void
    {
        $controller = $this->createController(enabled: true, childCount: 0);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $response = $controller->checkChildrenAction($request);

        self::assertSame(400, $response->getStatusCode());
        $content = json_decode($response->getBody()->getContents(), true);
        self::assertFalse($content['hasChildren']);
        self::assertSame(0, $content['childCount']);
    }

    private function createController(
        bool $enabled = true,
        bool $allowAdminBypass = false,
        int $childCount = 0,
        array $userGroupIds = [],
        bool $isAdmin = false,
    ): ChildPageCheckController {
        $config = [
            'enabled' => $enabled,
            'allowAdminBypass' => $allowAdminBypass,
        ];

        $extConfig = $this->createMock(ExtensionConfiguration::class);
        $extConfig->method('get')->willReturn($config);
        $settingsFactory = new SettingsFactory($extConfig);

        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn($userGroupIds);
        $userProvider->method('isAdmin')->willReturn($isAdmin);
        $userProvider->method('getWorkspaceId')->willReturn(0);
        $userProvider->method('isAuthenticated')->willReturn(true);
        $userProvider->method('getBackendUser')->willReturn(null);

        $connectionPool = $this->createMock(ConnectionPool::class);

        $statement = $this->createMock(Result::class);
        $statement->method('fetchOne')->willReturn($childCount);
        $statement->method('fetchAssociative')->willReturn(['uid' => 123, 'title' => 'Test Page']);

        $expr = $this->createMock(ExpressionBuilder::class);
        $expr->method('eq')->willReturn('uid = 123');

        $restrictionContainer = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictionContainer->method('removeAll')->willReturnSelf();
        $restrictionContainer->method('add')->willReturnSelf();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(static fn ($value) => (string) $value);
        $queryBuilder->method('executeQuery')->willReturn($statement);
        $queryBuilder->method('getRestrictions')->willReturn($restrictionContainer);

        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        $guardService = new PageDeletionGuardService($userProvider, $connectionPool);

        return new ChildPageCheckController($settingsFactory, $connectionPool, $guardService, $userProvider);
    }
}
