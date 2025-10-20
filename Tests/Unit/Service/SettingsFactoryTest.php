<?php

declare(strict_types=1);

namespace Wazum\PageDeletionGuard\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Wazum\PageDeletionGuard\Service\SettingsFactory;

final class SettingsFactoryTest extends TestCase
{
    #[Test]
    public function removesEmptyValuesFromGroupIdList(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'bypassBackendGroups' => '1, ,5, , 10',
        ]);

        $factory = new SettingsFactory($extensionConfiguration);
        $settings = $factory->create();

        self::assertSame([1, 5, 10], $settings->bypassGroupIds, 'Empty values should be filtered out');
        self::assertNotContains(0, $settings->bypassGroupIds, 'Group ID 0 should not be included');
    }
}
