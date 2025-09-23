<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Upgrade\v1_9_0;

class Config implements \M2E\Core\Model\Setup\Upgrade\Entity\ConfigInterface
{
    public function getFeaturesList(): array
    {
        return [
            \M2E\Temu\Setup\Update\y25_m08\CompleteWizards::class,
            \M2E\Temu\Setup\Update\y25_m08\AddValidationAttributesColumns::class,
            \M2E\Temu\Setup\Update\y25_m09\AddCustomValueToItemTaxCode::class,
        ];
    }
}
