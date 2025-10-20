<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Controller;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use Wazum\PageDeletionGuard\Service\PageDeletionGuardService;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final readonly class ChildPageCheckController
{
    public function __construct(
        private SettingsFactory $settingsFactory,
        private ConnectionPool $connectionPool,
        private PageDeletionGuardService $guardService,
    ) {
    }

    public function checkChildrenAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $pageUid = (int) ($queryParams['pageUid'] ?? 0);

        if ($pageUid <= 0) {
            return new JsonResponse([
                'hasChildren' => false,
                'childCount' => 0,
                'pageTitle' => '',
                'isAllowed' => false,
            ], 400);
        }

        try {
            $settings = $this->settingsFactory->create();

            // If guard is disabled, return no children so JS falls back to TYPO3's standard confirmation
            if (!$settings->enabled) {
                return new JsonResponse([
                    'hasChildren' => false,
                    'childCount' => 0,
                    'pageTitle' => '',
                    'isAllowed' => true,
                ]);
            }

            $pageRecord = $this->getPageRecord($pageUid);
            $childCount = $this->guardService->getChildCount($pageUid, $settings);
            $canBypass = $this->guardService->shouldBypass($settings);
            $isAllowedToDeleteWithChildren = $this->guardService->isUserAllowedToDeleteWithChildren($settings);
            $isAllowed = $canBypass || $isAllowedToDeleteWithChildren;

            return new JsonResponse([
                'hasChildren' => $childCount > 0,
                'childCount' => $childCount,
                'pageTitle' => $pageRecord['title'] ?? '',
                'isAllowed' => $isAllowed,
            ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'error' => 'Failed to check for child pages',
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    private function getPageRecord(int $pageUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new DeletedRestriction());

        $settings = $this->settingsFactory->create();
        if ($settings->respectWorkspaces) {
            $workspaceId = $GLOBALS['BE_USER']?->workspace ?? 0;
            $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($workspaceId));
        }

        $record = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid)))
            ->executeQuery()
            ->fetchAssociative();

        return is_array($record) ? $record : [];
    }
}
