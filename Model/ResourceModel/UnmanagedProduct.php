<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel;

class UnmanagedProduct extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_CHANNEL_PRODUCT_ID = 'channel_product_id';
    public const COLUMN_MAGENTO_PRODUCT_ID = 'magento_product_id';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_IMAGE_URL = 'image_url';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_SHIPPING_TEMPLATE_ID = 'shipping_template_id';
    public const COLUMN_MIN_PRICE = 'min_price';
    public const COLUMN_MAX_PRICE = 'max_price';
    public const COLUMN_CURRENCY_CODE = 'currency_code';
    public const COLUMN_QTY = 'qty';
    public const COLUMN_IS_SIMPLE = 'is_simple';
    public const COLUMN_INCOMPLETE_REASON = 'incomplete_reason';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_UNMANAGED_PRODUCT,
            self::COLUMN_ID
        );
    }
}
