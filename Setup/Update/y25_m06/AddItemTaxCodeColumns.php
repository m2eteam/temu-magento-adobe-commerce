<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m06;

use M2E\Temu\Helper\Module\Database\Tables;
use M2E\Temu\Model\ResourceModel\Policy\SellingFormat as SellingFormatResource;
use M2E\Temu\Model\ResourceModel\Product as ProductResource;

class AddItemTaxCodeColumns extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addItemTaxCodeToSellingPolicy();
        $this->addOnlineItemTaxCodeToProduct();
    }

    private function addItemTaxCodeToSellingPolicy(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_TEMPLATE_SELLING_FORMAT);

        $modifier->addColumn(
            SellingFormatResource::COLUMN_ITEM_TAX_CODE_ATTRIBUTE,
            'VARCHAR(255)',
            'NULL',
            SellingFormatResource::COLUMN_FIXED_PRICE_CUSTOM_ATTRIBUTE,
            false,
            false
        );

        $modifier->commit();
    }

    private function addOnlineItemTaxCodeToProduct(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_PRODUCT);

        $modifier->addColumn(
            ProductResource::COLUMN_ONLINE_ITEM_TAX_CODE,
            'VARCHAR(255)',
            'NULL',
            ProductResource::COLUMN_ONLINE_CATEGORIES_DATA,
            false,
            false
        );

        $modifier->commit();
    }
}
