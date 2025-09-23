<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Listing\Wizard;

class Product extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_WIZARD_ID = 'wizard_id';
    public const COLUMN_UNMANAGED_PRODUCT_ID = 'unmanaged_product_id';
    public const COLUMN_MAGENTO_PRODUCT_ID = 'magento_product_id';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_IS_VALID_CATEGORY_ATTRIBUTES = 'is_valid_category_attributes';
    public const COLUMN_CATEGORY_ATTRIBUTES_ERRORS = 'category_attributes_errors';
    public const COLUMN_IS_PROCESSED = 'is_processed';

    protected function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_LISTING_WIZARD_PRODUCT,
            self::COLUMN_ID
        );
    }
}
