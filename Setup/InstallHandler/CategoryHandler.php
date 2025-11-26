<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\InstallHandler;

use M2E\Temu\Helper\Module\Database\Tables as TablesHelper;
use M2E\Temu\Model\ResourceModel\Category\Attribute as CategoryAttributeResource;
use M2E\Temu\Model\ResourceModel\Category\Dictionary as CategoryResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class CategoryHandler implements \M2E\Core\Model\Setup\InstallHandlerInterface
{
    use HandlerTrait;

    public function installSchema(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $this->installCategoryDictionaryTable($setup);
        $this->installCategoryTreeTable($setup);
        $this->installTemplateCategoryAttributesTable($setup);
    }

    public function installData(\Magento\Framework\Setup\SetupInterface $setup): void
    {
    }

    private function installCategoryDictionaryTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_CATEGORY_DICTIONARY);
        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                CategoryResource::COLUMN_ID,
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
                CategoryResource::COLUMN_REGION,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                CategoryResource::COLUMN_CATEGORY_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                CategoryResource::COLUMN_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                CategoryResource::COLUMN_STATE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                CategoryResource::COLUMN_PATH,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                CategoryResource::COLUMN_SALES_ATTRIBUTES,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryResource::COLUMN_PRODUCT_ATTRIBUTES,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryResource::COLUMN_CATEGORY_RULES,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryResource::COLUMN_AUTHORIZED_BRANDS,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryResource::COLUMN_TOTAL_SALES_ATTRIBUTES,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_TOTAL_PRODUCT_ATTRIBUTES,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_USED_SALES_ATTRIBUTES,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_USED_PRODUCT_ATTRIBUTES,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_HAS_REQUIRED_PRODUCT_ATTRIBUTES,
                Table::TYPE_BOOLEAN,
                null,
                ['default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_HAS_REQUIRED_SALES_ATTRIBUTES,
                Table::TYPE_BOOLEAN,
                null,
                ['default' => 0]
            )
            ->addColumn(
                CategoryResource::COLUMN_IS_VALID,
                Table::TYPE_BOOLEAN,
                null,
                ['default' => 1]
            )
            ->addColumn(
                CategoryResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                CategoryResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            );

        $setup->getConnection()->createTable($table);
    }

    private function installCategoryTreeTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_CATEGORY_TREE);
        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::ID_FIELD,
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
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_REGION,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_CATEGORY_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_PARENT_CATEGORY_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => true,]
            )
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_IS_LEAF,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_PERMISSION_STATUSES,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addIndex(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_PARENT_CATEGORY_ID,
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_PARENT_CATEGORY_ID
            )
            ->addIndex(
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_REGION,
                \M2E\Temu\Model\ResourceModel\Category\Tree::COLUMN_REGION
            );

        $setup->getConnection()->createTable($table);
    }

    private function installTemplateCategoryAttributesTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_TEMPLATE_CATEGORY_ATTRIBUTES);
        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                CategoryAttributeResource::COLUMN_ID,
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
                CategoryAttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_TYPE,
                Table::TYPE_TEXT,
                30
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_ID,
                Table::TYPE_TEXT,
                50,
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_NAME,
                Table::TYPE_TEXT,
                50
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_RECOMMENDED,
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_CUSTOM_VALUE,
                Table::TYPE_TEXT,
                255,
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_CUSTOM_ATTRIBUTE,
                Table::TYPE_TEXT,
                255,
            )
            ->addIndex(
                'category_dictionary_id',
                CategoryAttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
            );

        $setup->getConnection()->createTable($table);
    }
}
