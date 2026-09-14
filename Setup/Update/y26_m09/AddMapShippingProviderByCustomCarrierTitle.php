<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y26_m09;

use M2E\Temu\Helper\Module\Database\Tables;

class AddMapShippingProviderByCustomCarrierTitle extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_ACCOUNT);

        $modifier->addColumn(
            \M2E\Temu\Model\ResourceModel\Account::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
            'SMALLINT NOT NULL',
            '0',
            \M2E\Temu\Model\ResourceModel\Account::COLUMN_CREATE_MAGENTO_SHIPMENT,
            false,
            false
        );

        $modifier->commit();
    }
}
