<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel;

class Account extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_SERVER_HASH = 'server_hash';
    public const COLUMN_IDENTIFIER = 'identifier';
    public const COLUMN_SITE_ID = 'site_id';
    public const COLUMN_SITE_TITLE = 'site_title';
    public const COLUMN_REGION = 'region';
    public const COLUMN_MAGENTO_ORDERS_SETTINGS = 'magento_orders_settings';
    public const COLUMN_CREATE_MAGENTO_INVOICE = 'create_magento_invoice';
    public const COLUMN_CREATE_MAGENTO_SHIPMENT = 'create_magento_shipment';
    public const COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE = 'map_shipping_provider_by_custom_carrier_title';
    public const COLUMN_SHIPPING_PROVIDER_MAPPING = 'shipping_provider_mapping';
    public const COLUMN_OTHER_LISTINGS_SYNCHRONIZATION = 'other_listings_synchronization';
    public const COLUMN_INVENTORY_LAST_SYNC_DATE = 'inventory_last_sync_date';
    public const COLUMN_OTHER_LISTINGS_MAPPING_MODE = 'other_listings_mapping_mode';
    public const COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS = 'other_listings_mapping_settings';
    public const COLUMN_OTHER_LISTINGS_RELATED_STORE_ID = 'other_listings_related_stores';
    public const COLUMN_ORDER_LAST_SYNC = 'orders_last_synchronization';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_ACCOUNT,
            self::COLUMN_ID
        );
    }
}
