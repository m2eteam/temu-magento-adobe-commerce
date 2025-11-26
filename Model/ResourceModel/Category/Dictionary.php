<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Category;

class Dictionary extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_REGION = 'region';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_STATE = 'state';
    public const COLUMN_PATH = 'path';
    public const COLUMN_SALES_ATTRIBUTES = 'sales_attributes';
    public const COLUMN_PRODUCT_ATTRIBUTES = 'product_attributes';
    public const COLUMN_CATEGORY_RULES = 'category_rules';
    public const COLUMN_AUTHORIZED_BRANDS = 'authorized_brands';
    public const COLUMN_TOTAL_SALES_ATTRIBUTES = 'total_sales_attributes';
    public const COLUMN_TOTAL_PRODUCT_ATTRIBUTES = 'total_product_attributes';
    public const COLUMN_USED_SALES_ATTRIBUTES = 'used_sales_attributes';
    public const COLUMN_USED_PRODUCT_ATTRIBUTES = 'used_product_attributes';
    public const COLUMN_HAS_REQUIRED_PRODUCT_ATTRIBUTES = 'has_required_product_attributes';
    public const COLUMN_HAS_REQUIRED_SALES_ATTRIBUTES = 'has_required_sales_attributes';
    public const COLUMN_IS_VALID = 'is_valid';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(\M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_CATEGORY_DICTIONARY, self::COLUMN_ID);
    }
}
