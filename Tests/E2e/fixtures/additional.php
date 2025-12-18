<?php

declare(strict_types=1);

/**
 * TYPO3 additional configuration for E2E tests.
 * Reads the X-Playwright-Test-ID header to apply different extension settings per test scenario.
 *
 * Copy this file to config/system/additional.php in your TYPO3 installation.
 */
$testId = $_SERVER['HTTP_X_PLAYWRIGHT_TEST_ID'] ?? 'default';

$settings = match ($testId) {
    'admin-bypass-off' => [
        'enabled' => true,
        'allowAdminBypass' => false,
        'bypassBackendGroups' => '1',
        'respectWorkspaces' => true,
    ],
    'guard-disabled' => [
        'enabled' => false,
        'allowAdminBypass' => true,
        'bypassBackendGroups' => '1',
        'respectWorkspaces' => true,
    ],
    'no-bypass-groups' => [
        'enabled' => true,
        'allowAdminBypass' => true,
        'bypassBackendGroups' => '',
        'respectWorkspaces' => true,
    ],
    default => [
        'enabled' => true,
        'allowAdminBypass' => true,
        'bypassBackendGroups' => '1',
        'respectWorkspaces' => true,
    ],
};

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['page_deletion_guard'] = $settings;

// Disable password policy for E2E tests to allow simple passwords like "docker"
$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] = '';
