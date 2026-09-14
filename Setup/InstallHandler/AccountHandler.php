<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\InstallHandler;

use M2E\Temu\Helper\Module\Database\Tables as TablesHelper;
use M2E\Temu\Model\ResourceModel\Account as AccountResource;
use M2E\Temu\Model\ResourceModel\ShippingProvider as ShippingProviderResource;
use Magento\Framework\DB\Ddl\Table;

class AccountHandler implements \M2E\Core\Model\Setup\InstallHandlerInterface
{
    use HandlerTrait;

    public function installSchema(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $this->installAccountTable($setup);
        $this->installShippingProvidersTable($setup);
    }

    private function installAccountTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_ACCOUNT);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                AccountResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                AccountResource::COLUMN_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_SERVER_HASH,
                Table::TYPE_TEXT,
                100,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_IDENTIFIER,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_SITE_ID,
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_SITE_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_REGION,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_ORDER_LAST_SYNC,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_MAGENTO_INVOICE,
                Table::TYPE_SMALLINT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT,
                Table::TYPE_SMALLINT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                AccountResource::COLUMN_SHIPPING_PROVIDER_MAPPING,
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION,
                Table::TYPE_SMALLINT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_INVENTORY_LAST_SYNC_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => '[]']
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORE_ID,
                Table::TYPE_INTEGER,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                AccountResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('title', AccountResource::COLUMN_TITLE)
            ->addIndex('identifier', AccountResource::COLUMN_IDENTIFIER)
            ->addIndex('site_id', AccountResource::COLUMN_SITE_ID)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    private function installShippingProvidersTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_SHIPPING_PROVIDERS);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ShippingProviderResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_NAME,
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_REGION_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_REGION_NAME,
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ]
            )
            ->addIndex('account_id', 'account_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    public function installData(\Magento\Framework\Setup\SetupInterface $setup): void
    {
    }
}
