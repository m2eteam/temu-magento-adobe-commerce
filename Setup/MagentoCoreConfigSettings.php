<?php

declare(strict_types=1);

namespace M2E\Temu\Setup;

class MagentoCoreConfigSettings implements \M2E\Core\Model\Setup\MagentoCoreConfigSettingsInterface
{
    public const MAGENTO_CORE_CONFIG_PREFIX = 'm2e_temu';

    public function getConfigKeyPrefix(): string
    {
        return self::MAGENTO_CORE_CONFIG_PREFIX;
    }
}
