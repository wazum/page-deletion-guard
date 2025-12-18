<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Wazum\PageDeletionGuard\Service\BackendUserProviderInterface;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final readonly class PageDeletionGuardHook
{
    private LanguageService $languageService;

    public function __construct(
        private SettingsFactory $settingsFactory,
        private FlashMessageService $flashMessageService,
        private PageDeletionGuardService $guardService,
        private BackendUserProviderInterface $userProvider,
        LanguageServiceFactory $languageServiceFactory,
    ) {
        $backendUser = $this->userProvider->getBackendUser();
        $this->languageService = $backendUser
            ? $languageServiceFactory->createFromUserPreferences($backendUser)
            : $languageServiceFactory->create('default');
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
        } catch (\Throwable) {
            // Block deletion if we cannot determine child count.
            // This sounds counterintuitive, but setting this to true prevents the deletion (and further processing).
            $recordWasDeleted = true;

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
    private function blockDeletion(
        string $table,
        int $id,
        array $record,
        int $childCount,
        bool &$recordWasDeleted,
        DataHandler $dataHandler,
    ): void {
        $pageTitle = $record['title'] ?? '';
        $flashMessageText = sprintf(
            $this->languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.message'),
            $pageTitle,
            $childCount
        );

        $logMessage = sprintf(
            $this->languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:log.denied'),
            $pageTitle,
            $id,
            $childCount
        );

        $flashTitle = $this->languageService->sL('LLL:EXT:page_deletion_guard/Resources/Private/Language/locallang.xlf:flash.title');
        $flashMessage = new FlashMessage($flashMessageText, $flashTitle, ContextualFeedbackSeverity::ERROR);
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);

        $dataHandler->log($table, $id, 3, null, 0, $logMessage);

        // This sounds counterintuitive, but setting this to true prevents the deletion (and further processing).
        $recordWasDeleted = true;
    }
}
