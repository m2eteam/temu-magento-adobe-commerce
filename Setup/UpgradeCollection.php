<?php

declare(strict_types=1);

namespace M2E\Temu\Setup;

class UpgradeCollection extends \M2E\Core\Model\Setup\AbstractUpgradeCollection
{
    public function getMinAllowedVersion(): string
    {
        return '1.0.0';
    }

    protected function getSourceVersionUpgrades(): array
    {
        return [
            '1.0.0' => ['to' => '1.1.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_1_0\Config::class],
            '1.1.0' => ['to' => '1.2.0', 'upgrade' => null],
            '1.2.0' => ['to' => '1.2.1', 'upgrade' => null],
            '1.2.1' => ['to' => '1.2.2', 'upgrade' => null],
            '1.2.2' => ['to' => '1.2.3', 'upgrade' => null],
            '1.2.3' => ['to' => '1.3.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_3_0\Config::class],
            '1.3.0' => ['to' => '1.4.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_4_0\Config::class],
            '1.4.0' => ['to' => '1.4.1', 'upgrade' => null],
            '1.4.1' => ['to' => '1.5.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_5_0\Config::class],
            '1.5.0' => ['to' => '1.5.1', 'upgrade' => null],
            '1.5.1' => ['to' => '1.6.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_6_0\Config::class],
            '1.6.0' => ['to' => '1.7.0', 'upgrade' => null],
            '1.7.0' => ['to' => '1.8.0', 'upgrade' => null],
            '1.8.0' => ['to' => '1.9.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_9_0\Config::class],
            '1.9.0' => ['to' => '1.10.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_10_0\Config::class],
            '1.10.0' => ['to' => '1.11.0', 'upgrade' => null],
            '1.11.0' => ['to' => '1.12.0', 'upgrade' => \M2E\Temu\Setup\Upgrade\v1_12_0\Config::class],
            '1.12.0' => ['to' => '1.12.1', 'upgrade' => null],
        ];
    }
}
