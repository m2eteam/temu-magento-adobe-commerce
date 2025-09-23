<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\InstallHandler;

use M2E\Temu\Helper\Module\Database\Tables as TablesHelper;
use M2E\Temu\Model\ResourceModel\Listing as ListingResource;
use M2E\Temu\Model\ResourceModel\Listing\Wizard as ListingWizardResource;
use M2E\Temu\Model\ResourceModel\Listing\Wizard\Product as ListingWizardProductResource;
use M2E\Temu\Model\ResourceModel\Listing\Wizard\Step as ListingStepResource;
use Magento\Framework\DB\Ddl\Table;

class ListingHandler implements \M2E\Core\Model\Setup\InstallHandlerInterface
{
    use HandlerTrait;

    public function installSchema(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $this->installListingTable($setup);
        $this->installListingWizardTable($setup);
        $this->installListingWizardStepTable($setup);
        $this->installListingWizardProductTable($setup);
    }

    private function installListingTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_LISTING);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ListingResource::COLUMN_ID,
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
                ListingResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ListingResource::COLUMN_SITE_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ListingResource::COLUMN_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ListingResource::COLUMN_STORE_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_TEMPLATE_SHIPPING_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_ADDITIONAL_DATA,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ListingResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('account_id', ListingResource::COLUMN_ACCOUNT_ID)
            ->addIndex('store_id', ListingResource::COLUMN_STORE_ID)
            ->addIndex('title', ListingResource::COLUMN_TITLE)
            ->addIndex('template_description_id', ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID)
            ->addIndex('template_selling_format_id', ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID)
            ->addIndex('template_synchronization_id', ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID)
            ->addIndex('template_shipping_id', ListingResource::COLUMN_TEMPLATE_SHIPPING_ID)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    private function installListingWizardTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_LISTING_WIZARD);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ListingWizardResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_LISTING_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_TYPE,
                Table::TYPE_TEXT,
                50,
            )
            ->addColumn(
                ListingWizardResource::COLUMN_CURRENT_STEP_NICK,
                Table::TYPE_TEXT,
                150,
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PRODUCT_COUNT_TOTAL,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_IS_COMPLETED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PROCESS_START_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_PROCESS_END_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingWizardResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addIndex('listing_id', ListingWizardResource::COLUMN_LISTING_ID)
            ->addIndex('is_completed', ListingWizardResource::COLUMN_IS_COMPLETED)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    private function installListingWizardStepTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_LISTING_WIZARD_STEP);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ListingStepResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingStepResource::COLUMN_WIZARD_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingStepResource::COLUMN_NICK,
                Table::TYPE_TEXT,
                150,
            )
            ->addColumn(
                ListingStepResource::COLUMN_DATA,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['default' => null],
            )
            ->addColumn(
                ListingStepResource::COLUMN_IS_COMPLETED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingStepResource::COLUMN_IS_SKIPPED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingStepResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addColumn(
                ListingStepResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null],
            )
            ->addIndex('wizard_id', ListingStepResource::COLUMN_WIZARD_ID)
            ->addIndex('is_completed', ListingStepResource::COLUMN_IS_COMPLETED)
            ->addIndex('is_skipped', ListingStepResource::COLUMN_IS_SKIPPED)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    private function installListingWizardProductTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_LISTING_WIZARD_PRODUCT);

        $productTable = $setup->getConnection()->newTable($tableName);

        $productTable
            ->addColumn(
                ListingWizardProductResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_WIZARD_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_CATEGORY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_IS_PROCESSED,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
            )
            ->addColumn(
                ListingWizardProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
                ['nullable' => true, 'default' => null]
            )
            ->addIndex('wizard_id', ListingWizardProductResource::COLUMN_WIZARD_ID)
            ->addIndex('category_id', ListingWizardProductResource::COLUMN_CATEGORY_ID)
            ->addIndex('is_processed', ListingWizardProductResource::COLUMN_IS_PROCESSED)
            ->addIndex(
                'wizard_id_magento_product_id',
                [
                    ListingWizardProductResource::COLUMN_WIZARD_ID,
                    ListingWizardProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                ],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE],
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($productTable);
    }

    public function installData(\Magento\Framework\Setup\SetupInterface $setup): void
    {
    }
}
