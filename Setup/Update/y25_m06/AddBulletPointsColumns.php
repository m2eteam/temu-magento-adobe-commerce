<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m06;

use M2E\Temu\Helper\Module\Database\Tables;
use M2E\Temu\Model\ResourceModel\Policy\Description as DescriptionResource;
use M2E\Temu\Model\ResourceModel\Product as ProductResource;

class AddBulletPointsColumns extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addBulletPointsToDescPolicy();
        $this->addBulletPointsToProduct();
    }

    private function addBulletPointsToDescPolicy(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_TEMPLATE_DESCRIPTION);

        $modifier->addColumn(
            DescriptionResource::COLUMN_BULLET_POINTS,
            'LONGTEXT',
            'NULL',
            DescriptionResource::COLUMN_GALLERY_IMAGES_ATTRIBUTE,
            false,
            false
        );

        $modifier->commit();
    }

    private function addBulletPointsToProduct(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_PRODUCT);

        $modifier->addColumn(
            ProductResource::COLUMN_ONLINE_BULLET_POINTS,
            'VARCHAR(255)',
            'NULL',
            ProductResource::COLUMN_ONLINE_CATEGORIES_DATA,
            false,
            false
        );

        $modifier->commit();
    }
}
