<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Hook;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use Wazum\PageDeletionGuard\Hook\PageDeletionGuardHook;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final class PageDeletionGuardHookTest extends TestCase
{
    #[Test]
    public function ignoresNonPagesTables(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $recordWasDeleted = false;

        $hook = $this->createHook(childCount: 2);
        $hook->processCmdmap_deleteAction('tt_content', 1, [], $recordWasDeleted, $dataHandler);

        self::assertFalse($recordWasDeleted, 'Should ignore non-pages tables even when mock has children');
    }

    #[Test]
    public function allowsDeletionWhenGuardDisabled(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $recordWasDeleted = false;

        $hook = $this->createHook(enabled: false, childCount: 2);
        $hook->processCmdmap_deleteAction('pages', 1, [], $recordWasDeleted, $dataHandler);

        self::assertFalse($recordWasDeleted, 'Should allow deletion when guard is disabled even if children exist');
    }

    #[Test]
    public function blocksDeletionWhenPageHasChildren(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $dataHandler->expects(self::once())
            ->method('log')
            ->with(
                'pages',
                1,
                self::anything(),
                null,
                0,
                self::stringContains('2 child page(s)')
            );
        $recordWasDeleted = false;

        $hook = $this->createHook(childCount: 2);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertTrue($recordWasDeleted, 'recordWasDeleted must be true to stop DataHandler from proceeding');
    }

    #[Test]
    public function allowsDeletionWhenPageHasNoChildren(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $recordWasDeleted = false;

        $hook = $this->createHook(childCount: 0);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertFalse($recordWasDeleted, 'Deletion should be allowed when no children exist');
    }

    #[Test]
    public function allowsAdminBypassWhenEnabled(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $recordWasDeleted = false;

        $hook = $this->createHook(allowAdminBypass: true, childCount: 2, isAdmin: true);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertFalse($recordWasDeleted, 'Admin should bypass deletion guard when allowAdminBypass is enabled');
    }

    #[Test]
    public function blocksAdminWhenBypassDisabled(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->expects(self::once())->method('log');
        $recordWasDeleted = false;

        $hook = $this->createHook(allowAdminBypass: false, childCount: 2, isAdmin: true);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertTrue($recordWasDeleted, 'Admin should not bypass deletion guard when allowAdminBypass is disabled');
    }

    #[Test]
    public function allowsBypassForConfiguredBackendGroups(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $recordWasDeleted = false;

        $hook = $this->createHook(bypassGroupIds: [5, 10], userGroupIds: [2, 5], childCount: 2);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertFalse($recordWasDeleted, 'User in bypass group should bypass deletion guard');
    }

    #[Test]
    public function blocksUserNotInBypassGroup(): void
    {
        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->admin = false;
        $dataHandler->expects(self::once())->method('log');
        $recordWasDeleted = false;

        $hook = $this->createHook(bypassGroupIds: [5, 10], userGroupIds: [2, 3], childCount: 2);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertTrue($recordWasDeleted, 'User not in bypass group should be blocked');
    }

    private function createHook(
        bool $enabled = true,
        bool $allowAdminBypass = true,
        array $bypassGroupIds = [],
        array $userGroupIds = [],
        int $childCount = 0,
        bool $isAdmin = false,
    ): PageDeletionGuardHook {
        $this->setUpLanguageService();

        $config = [
            'enabled' => $enabled,
            'allowAdminBypass' => $allowAdminBypass,
        ];

        if ([] !== $bypassGroupIds) {
            $config['bypassBackendGroups'] = implode(',', $bypassGroupIds);
        }

        $extConfig = $this->createMock(ExtensionConfiguration::class);
        $extConfig->method('get')->willReturn($config);
        $settingsFactory = new SettingsFactory($extConfig);

        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn($userGroupIds);
        $userProvider->method('isAdmin')->willReturn($isAdmin);
        $userProvider->method('getWorkspaceId')->willReturn(0);
        $userProvider->method('isAuthenticated')->willReturn(true);

        $connectionPool = $this->createMock(ConnectionPool::class);

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

        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        $flashMessageService = $this->createMock(FlashMessageService::class);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->willReturn($flashMessageQueue);

        $guardService = new PageDeletionGuardService($userProvider, $connectionPool);

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static function (string $key) {
            return match ($key) {
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title' => 'Page Deletion Blocked',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.message' => 'Cannot delete page "%s": %d child page(s) exist. Delete child pages first.',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.denied' => 'Page deletion blocked: "%s" (UID %d) has %d child page(s)',
                default => $key,
            };
        });

        $languageServiceFactory = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactory->method('createFromUserPreferences')->willReturn($languageService);
        $languageServiceFactory->method('create')->willReturn($languageService);

        return new PageDeletionGuardHook($settingsFactory, $flashMessageService, $guardService, $languageServiceFactory);
    }

    private function setUpLanguageService(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUser;

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static function (string $key) {
            return match ($key) {
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title' => 'Page Deletion Blocked',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.message' => 'Cannot delete page "%s": %d child page(s) exist. Delete child pages first.',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.denied' => 'Page deletion blocked: "%s" (UID %d) has %d child page(s)',
                default => $key,
            };
        });
        $GLOBALS['LANG'] = $languageService;
    }
}
