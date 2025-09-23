<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m08;

use M2E\Temu\Helper\Module\Database\Tables;
use M2E\Temu\Model\ResourceModel\Listing\Wizard\Product as ListingWizardProduct;
use M2E\Temu\Model\ResourceModel\Product as Product;

class AddValidationAttributesColumns extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->modifyListingWizardProductTable();
        $this->modifyProductTable();
    }

    private function modifyListingWizardProductTable(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_LISTING_WIZARD_PRODUCT);

        $modifier->addColumn(
            ListingWizardProduct::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
            'SMALLINT UNSIGNED',
            'NULL',
            ListingWizardProduct::COLUMN_IS_PROCESSED,
            false,
            false
        );

        $modifier->addColumn(
            ListingWizardProduct::COLUMN_CATEGORY_ATTRIBUTES_ERRORS,
            'LONGTEXT',
            'NULL',
            ListingWizardProduct::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
            false,
            false
        );

        $modifier->commit();
    }

    private function modifyProductTable(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_PRODUCT);

        $modifier->addColumn(
            Product::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
            'SMALLINT UNSIGNED',
            'NULL',
            Product::COLUMN_TEMPLATE_CATEGORY_ID,
            false,
            false
        );

        $modifier->addColumn(
            Product::COLUMN_CATEGORY_ATTRIBUTES_ERRORS,
            'LONGTEXT',
            'NULL',
            Product::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
            false,
            false
        );

        $modifier->commit();
    }
}
