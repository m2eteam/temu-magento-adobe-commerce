<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m09;

use M2E\Temu\Helper\Module\Database\Tables;
use M2E\Temu\Model\ResourceModel\Policy\SellingFormat as SellingFormatResource;

class AddCustomValueToItemTaxCode extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addNewColumns();
        $this->setItemTaxCodeMode();
    }

    private function addNewColumns(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_TEMPLATE_SELLING_FORMAT);

        $modifier->addColumn(
            SellingFormatResource::COLUMN_ITEM_TAX_CODE_MODE,
            'SMALLINT UNSIGNED',
            'NULL',
            SellingFormatResource::COLUMN_REFERENCE_LINK_ATTRIBUTE,
            false,
            false
        );

        $modifier->addColumn(
            SellingFormatResource::COLUMN_ITEM_TAX_CODE_CUSTOM_VALUE,
            'VARCHAR(255)',
            'NULL',
            SellingFormatResource::COLUMN_ITEM_TAX_CODE_ATTRIBUTE,
            false,
            false
        );

        $modifier->commit();
    }

    private function setItemTaxCodeMode(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName(Tables::TABLE_NAME_TEMPLATE_SELLING_FORMAT),
            [
                SellingFormatResource::COLUMN_ITEM_TAX_CODE_MODE
                => \M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_NONE
            ],
            sprintf('%s IS NULL', SellingFormatResource::COLUMN_ITEM_TAX_CODE_ATTRIBUTE)
        );

        $this->getConnection()->update(
            $this->getFullTableName(Tables::TABLE_NAME_TEMPLATE_SELLING_FORMAT),
            [
                SellingFormatResource::COLUMN_ITEM_TAX_CODE_MODE
                => \M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE
            ],
            sprintf('%s IS NOT NULL', SellingFormatResource::COLUMN_ITEM_TAX_CODE_ATTRIBUTE)
        );
    }
}
