<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m10;

use M2E\Temu\Helper\Module\Database\Tables;

class SupportIncompleteProductsInExternalChanges extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addIncompleteReasonColumnToUnmanagedProduct();
        $this->addIncompleteReasonColumnToProduct();
    }

    private function addIncompleteReasonColumnToUnmanagedProduct(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_UNMANAGED_PRODUCT);

        $modifier->addColumn(
            \M2E\Temu\Model\ResourceModel\UnmanagedProduct::COLUMN_INCOMPLETE_REASON,
            'VARCHAR(255)',
            'NULL',
            null,
            false,
            false
        );

        $modifier->commit();
    }

    private function addIncompleteReasonColumnToProduct(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_PRODUCT);

        $modifier->addColumn(
            \M2E\Temu\Model\ResourceModel\Product::COLUMN_INCOMPLETE_REASON,
            'VARCHAR(255)',
            'NULL',
            null,
            false,
            false
        );

        $modifier->commit();
    }
}
