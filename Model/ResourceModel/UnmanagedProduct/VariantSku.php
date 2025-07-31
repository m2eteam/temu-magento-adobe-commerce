<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\UnmanagedProduct;

class VariantSku extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_PRODUCT_ID = 'product_id';
    public const COLUMN_MAGENTO_PRODUCT_ID = 'magento_product_id';
    public const COLUMN_SKU_ID = 'sku_id';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_SKU = 'sku';
    public const COLUMN_IMAGE_URL = 'image_url';
    public const COLUMN_PRICE = 'price';
    public const COLUMN_RETAIL_PRICE = 'retail_price';
    public const COLUMN_CURRENCY = 'currency';
    public const COLUMN_QTY = 'qty';
    public const COLUMN_IDENTIFIERS = 'identifiers';
    public const COLUMN_SPECIFICATION = 'specification';
    public const COLUMN_QTY_ACTUALIZE_DATE = 'qty_actualize_date';
    public const COLUMN_PRICE_ACTUALIZE_DATE = 'price_actualize_date';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_UNMANAGED_PRODUCT_VARIANT_SKU,
            self::COLUMN_ID
        );
    }
}
