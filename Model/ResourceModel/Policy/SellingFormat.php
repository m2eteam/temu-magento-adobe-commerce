<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Policy;

class SellingFormat extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_IS_CUSTOM_TEMPLATE = 'is_custom_template';
    public const COLUMN_QTY_MODE = 'qty_mode';
    public const COLUMN_QTY_CUSTOM_VALUE = 'qty_custom_value';
    public const COLUMN_QTY_CUSTOM_ATTRIBUTE = 'qty_custom_attribute';
    public const COLUMN_QTY_PERCENTAGE = 'qty_percentage';
    public const COLUMN_QTY_MODIFICATION_MODE = 'qty_modification_mode';
    public const COLUMN_QTY_MIN_POSTED_VALUE = 'qty_min_posted_value';
    public const COLUMN_QTY_MAX_POSTED_VALUE = 'qty_max_posted_value';
    public const COLUMN_FIXED_PRICE_MODE = 'fixed_price_mode';
    public const COLUMN_FIXED_PRICE_MODIFIER = 'fixed_price_modifier';
    public const COLUMN_FIXED_PRICE_CUSTOM_ATTRIBUTE = 'fixed_price_custom_attribute';
    public const COLUMN_REFERENCE_LINK_ATTRIBUTE = 'reference_link_attribute';
    public const COLUMN_ITEM_TAX_CODE_ATTRIBUTE = 'item_tax_code_attribute';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_TEMPLATE_SELLING_FORMAT,
            self::COLUMN_ID
        );
    }
}
