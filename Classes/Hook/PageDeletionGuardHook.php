<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SysLog\Action\Database as DatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final readonly class PageDeletionGuardHook
{
    public function __construct(
        private SettingsFactory $settingsFactory,
        private FlashMessageService $flashMessageService,
        private PageDeletionGuardService $guardService,
        private BackendUserProviderInterface $userProvider,
        private LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    /**
     * @throws Exception
     */
    public function processCmdmap_deleteAction(
        string $table,
        int $id,
        array $record,
        bool &$recordWasDeleted,
        DataHandler $dataHandler,
    ): void {
        if ('pages' !== $table) {
            return;
        }

        $settings = $this->settingsFactory->create();
        if ($this->guardService->shouldBypass($settings)) {
            return;
        }

        try {
            $childCount = $this->guardService->getChildCount($id, $settings);
        } catch (\Throwable $exception) {
            $this->blockOnError($table, $id, $record, $exception, $recordWasDeleted, $dataHandler);

            return;
        }

        if ($childCount <= 0) {
            return;
        }

        $this->blockDeletion($table, $id, $record, $childCount, $recordWasDeleted, $dataHandler);
    }

    /**
     * @throws Exception
     */
    private function blockOnError(
        string $table,
        int $id,
        array $record,
        \Throwable $exception,
        bool &$recordWasDeleted,
        DataHandler $dataHandler,
    ): void {
        $languageService = $this->getLanguageService();
        $pageTitle = $record['title'] ?? '';
        $flashMessageText = sprintf(
            $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.error.message'),
            $pageTitle
        );
        $logMessage = sprintf(
            $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.error'),
            $pageTitle,
            $id,
            $exception->getMessage()
        );

        $flashTitle = $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title');
        $flashMessage = new FlashMessage($flashMessageText, $flashTitle, ContextualFeedbackSeverity::ERROR);
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);

        $dataHandler->log($table, $id, DatabaseAction::DELETE, null, SystemLogErrorClassification::USER_ERROR, $logMessage);

        // Setting recordWasDeleted to true short-circuits DataHandler and prevents the deletion.
        $recordWasDeleted = true;
    }

    /**
     * @throws Exception
     */
    private function blockDeletion(
        string $table,
        int $id,
        array $record,
        int $childCount,
        bool &$recordWasDeleted,
        DataHandler $dataHandler,
    ): void {
        $languageService = $this->getLanguageService();
        $pageTitle = $record['title'] ?? '';
        $flashMessageText = sprintf(
            $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.message'),
            $pageTitle,
            $childCount
        );

        $logMessage = sprintf(
            $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.denied'),
            $pageTitle,
            $id,
            $childCount
        );

        $flashTitle = $languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title');
        $flashMessage = new FlashMessage($flashMessageText, $flashTitle, ContextualFeedbackSeverity::ERROR);
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);

        $dataHandler->log($table, $id, DatabaseAction::DELETE, null, SystemLogErrorClassification::USER_ERROR, $logMessage);

        // This sounds counterintuitive, but setting this to true prevents the deletion (and further processing).
        $recordWasDeleted = true;
    }

    private function getLanguageService(): LanguageService
    {
        $backendUser = $this->userProvider->getBackendUser();

        return $backendUser
            ? $this->languageServiceFactory->createFromUserPreferences($backendUser)
            : $this->languageServiceFactory->create('default');
    }
}
