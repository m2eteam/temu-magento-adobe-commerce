<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m07;

use M2E\Temu\Helper\Module\Database\Tables;

class UnmanagedConfigurableProducts extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->dropSalesAttributesColumn();
        $this->modifyRemovedMagentoProductIdColumn();
    }

    private function dropSalesAttributesColumn()
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_UNMANAGED_PRODUCT_VARIANT_SKU);

        $modifier->dropColumn('sales_attributes');

        $modifier->commit();
    }

    private function modifyRemovedMagentoProductIdColumn()
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_PRODUCT_VARIANT_SKU_DELETED);

        $modifier->changeColumn(
            \M2E\Temu\Model\ResourceModel\Product\VariantSku\Deleted::COLUMN_REMOVED_MAGENTO_PRODUCT_ID,
            'INT UNSIGNED'
        );

        $modifier->commit();
    }
}
