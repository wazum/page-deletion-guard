<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Hook;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
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
                SystemLogErrorClassification::USER_ERROR,
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

    #[Test]
    public function logsAndFlashesWhenChildCountQueryFails(): void
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
                self::anything(),
                self::stringContains('Failed to check child pages')
            );
        $recordWasDeleted = false;

        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageQueue->expects(self::once())
            ->method('enqueue')
            ->with(self::isInstanceOf(FlashMessage::class));

        $hook = $this->createHook(queryException: new \RuntimeException('boom'), flashMessageQueue: $flashMessageQueue);
        $hook->processCmdmap_deleteAction('pages', 1, ['title' => 'Test Page'], $recordWasDeleted, $dataHandler);

        self::assertTrue($recordWasDeleted, 'Deletion must be blocked when child count cannot be determined');
    }

    private function createHook(
        bool $enabled = true,
        bool $allowAdminBypass = true,
        array $bypassGroupIds = [],
        array $userGroupIds = [],
        int $childCount = 0,
        bool $isAdmin = false,
        ?\Throwable $queryException = null,
        ?FlashMessageQueue $flashMessageQueue = null,
    ): PageDeletionGuardHook {
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

        $backendUser = $this->createMock(BackendUserAuthentication::class);

        $userProvider = $this->createMock(BackendUserProviderInterface::class);
        $userProvider->method('getUserGroupIds')->willReturn($userGroupIds);
        $userProvider->method('isAdmin')->willReturn($isAdmin);
        $userProvider->method('getWorkspaceId')->willReturn(0);
        $userProvider->method('isAuthenticated')->willReturn(true);
        $userProvider->method('getBackendUser')->willReturn($backendUser);

        $connectionPool = $this->createMock(ConnectionPool::class);

        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn($childCount);

        $connection = $this->createMock(Connection::class);
        if (null !== $queryException) {
            $connection->method('executeQuery')->willThrowException($queryException);
        } else {
            $connection->method('executeQuery')->willReturn($result);
        }
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        $flashMessageService = $this->createMock(FlashMessageService::class);
        $flashMessageQueue ??= $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->willReturn($flashMessageQueue);

        $guardService = new PageDeletionGuardService($userProvider, $connectionPool);

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static function (string $key) {
            return match ($key) {
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title' => 'Page Deletion Blocked',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.message' => 'Cannot delete page "%s": %d child page(s) exist. Delete child pages first.',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.error.message' => 'Failed to check child pages of "%s". Deletion was blocked as a safety measure.',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.denied' => 'Page deletion blocked: "%s" (UID %d) has %d child page(s)',
                'LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.error' => 'Failed to check child pages of "%s" (UID %d): %s. Deletion was blocked as a safety measure.',
                default => $key,
            };
        });

        $languageServiceFactory = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactory->method('createFromUserPreferences')->willReturn($languageService);
        $languageServiceFactory->method('create')->willReturn($languageService);

        return new PageDeletionGuardHook($settingsFactory, $flashMessageService, $guardService, $userProvider, $languageServiceFactory);
    }
}
