<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class SettingsFactory
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    public function create(): Settings
    {
        try {
            $config = $this->extensionConfiguration->get('page_deletion_guard');
        } catch (\Throwable) {
            $config = [];
        }

        $groupIds = [];
        if (isset($config['bypassBackendGroups']) && is_string($config['bypassBackendGroups'])) {
            $groupIds = array_values(GeneralUtility::intExplode(',', $config['bypassBackendGroups'], true));
        }

        return new Settings(
            enabled: (bool) ($config['enabled'] ?? true),
            allowAdminBypass: (bool) ($config['allowAdminBypass'] ?? true),
            bypassGroupIds: $groupIds,
            respectWorkspaces: (bool) ($config['respectWorkspaces'] ?? true),
        );
    }
}
