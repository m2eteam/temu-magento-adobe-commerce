<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category;

use M2E\Temu\Model\ResourceModel\Category\Dictionary\CollectionFactory as DictionaryCollectionFactory;

class Grid extends \M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private DictionaryCollectionFactory $categoryDictionaryCollectionFactory;
    private \M2E\Temu\Model\ResourceModel\Product $productResource;
    private \M2E\Core\Ui\AppliedFilters\Manager $appliedFiltersManager;

    public function __construct(
        DictionaryCollectionFactory $categoryDictionaryCollectionFactory,
        \M2E\Temu\Model\ResourceModel\Product $productResource,
        \M2E\Core\Ui\AppliedFilters\Manager $appliedFiltersManager,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->categoryDictionaryCollectionFactory = $categoryDictionaryCollectionFactory;
        $this->productResource = $productResource;
        $this->appliedFiltersManager = $appliedFiltersManager;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('temuTemplateCategoryGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
        $this->setDefaultDir('asc');
    }

    protected function _prepareCollection()
    {
        $collection = $this->categoryDictionaryCollectionFactory->create();

        $collection->joinLeft(
            ['products' => $this->createProductCountJoinTable()],
            'template_category_id = id',
            ['product_count' => 'count']
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'category_id',
            [
                'header' => __('Category ID'),
                'align' => 'center',
                'type' => 'text',
                'index' => \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_CATEGORY_ID,
            ]
        );

        $this->addColumn(
            'path',
            [
                'header' => __('Title'),
                'align' => 'left',
                'type' => 'text',
                'escape' => true,
                'index' => \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_PATH,
                'filter_condition_callback' => [$this, 'callbackFilterPath'],
                'frame_callback' => [$this, 'callbackColumnFilterPath'],
            ]
        );

        $this->addColumn(
            'region',
            [
                'header' => __('Region'),
                'align' => 'left',
                'type' => 'text',
                'width' => '100px',
                'index' => \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_REGION,
                'frame_callback' => [$this, 'callbackColumnRegion'],
                'filter_condition_callback' => [$this, 'callbackFilterRegion'],
            ]
        );

        $this->addColumn(
            'product_count',
            [
                'header' => __('Products'),
                'align' => 'center',
                'type' => 'number',
                'index' => 'product_count',
                'filter_index' => 'products.count',
                'frame_callback' => [$this, 'callbackColumnProductCount'],
            ]
        );

        $this->addColumn(
            'total_attributes',
            [
                'header' => __('Attributes: Total'),
                'align' => 'left',
                'type' => 'text',
                'width' => '100px',
                'index' => \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_TOTAL_PRODUCT_ATTRIBUTES,
                'filter' => false,
            ]
        );

        $this->addColumn(
            'used_attributes',
            [
                'header' => __('Attributes: Used'),
                'align' => 'left',
                'type' => 'text',
                'width' => '100px',
                'index' => \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_USED_PRODUCT_ATTRIBUTES,
                'filter' => false,
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header' => __('Actions'),
                'align' => 'left',
                'width' => '70px',
                'type' => 'action',
                'index' => 'actions',
                'filter' => false,
                'sortable' => false,
                'renderer' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => '*/category/view',
                            'params' => [
                                'dictionary_id' => '$id',
                            ],
                        ],
                        'field' => 'id',
                    ],
                ],
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Remove'),
                'url' => $this->getUrl('*/category/delete'),
                'confirm' => __('Are you sure?'),
            ]
        );

        return parent::_prepareMassaction();
    }

    public function callbackColumnFilterPath($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return '';
        }

        if (!$row->isCategoryValid()) {
            return sprintf(
                '%s <span style="color: #f00;">%s</span>',
                $row->getPath(),
                __('Invalid')
            );
        }

        return $row->getPath();
    }

    protected function callbackFilterPath($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.path LIKE ?', '%' . $value . '%');
    }

    public function callbackColumnRegion(
        $value,
        \M2E\Temu\Model\Category\Dictionary $dictionary,
        $column,
        $isExport
    ): string {
        return ucfirst($dictionary->getRegion());
    }

    protected function callbackFilterRegion($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.region LIKE ?', '%' . $value . '%');
    }

    public function callbackColumnProductCount(
        ?string $value,
        \M2E\Temu\Model\Category\Dictionary $dictionary,
        \M2E\Temu\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column,
        bool $isExport
    ): string {
        if (empty($value)) {
            return '0';
        }

        $appliedFiltersBuilder = new \M2E\Core\Ui\AppliedFilters\Builder();
        $appliedFiltersBuilder->addSelectFilter('product_template_category_id', [$dictionary->getId()]);

        $url = $this->appliedFiltersManager->createUrlWithAppliedFilters(
            '*/product_grid/allItems',
            $appliedFiltersBuilder->build()
        );

        return sprintf('<a href="%s" target="_blank">%s</a>', $url, $value);
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    private function createProductCountJoinTable(): \Magento\Framework\DB\Select
    {
        return $this->productResource
            ->getConnection()
            ->select()
            ->from(
                ['temp' => $this->productResource->getMainTable()],
                [
                    'template_category_id' => $this->productResource::COLUMN_TEMPLATE_CATEGORY_ID,
                    'count' => new \Zend_Db_Expr('COUNT(*)'),
                ]
            )
            ->group($this->productResource::COLUMN_TEMPLATE_CATEGORY_ID);
    }
}
