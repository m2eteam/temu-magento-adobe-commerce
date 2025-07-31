<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Listing\Mapping;

class Grid extends \M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    protected \Magento\Catalog\Model\Product\Type $type;
    protected \M2E\Temu\Helper\Magento\Product $magentoProductHelper;
    private \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Ui\UrlHelper $urlHelper;

    public function __construct(
        \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory,
        \M2E\Temu\Helper\Magento\Product $magentoProductHelper,
        \M2E\Temu\Model\UnmanagedProduct\Ui\UrlHelper $urlHelper,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->type = $type;
        $this->magentoProductHelper = $magentoProductHelper;
        $this->ourMagentoProductFactory = $ourMagentoProductFactory;
        $this->urlHelper = $urlHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('listingMappingGrid');

        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('type_id');

        $collection->joinStockItem();

        $collection->addFieldToFilter(
            [
                [
                    'attribute' => 'type_id',
                    'in' => [
                        $this->getData('product_type'),
                    ],
                ],
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_id',
            [
                'header' => __('Product ID'),
                'align' => 'right',
                'type' => 'number',
                'width' => '100px',
                'index' => 'entity_id',
                'filter_index' => 'entity_id',
                'renderer' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
            ]
        );

        $this->addColumn(
            'title',
            [
                'header' => __('Product Title / Product SKU'),
                'align' => 'left',
                'type' => 'text',
                'width' => '200px',
                'index' => 'name',
                'filter_index' => 'name',
                'escape' => false,
                'frame_callback' => [$this, 'callbackColumnTitle'],
                'filter_condition_callback' => [$this, 'callbackFilterTitle'],
            ]
        );

        $this->addColumn(
            'stock_availability',
            [
                'header' => __('Stock Availability'),
                'width' => '100px',
                'index' => 'is_in_stock',
                'filter_index' => 'is_in_stock',
                'type' => 'options',
                'sortable' => false,
                'options' => [
                    1 => __('In Stock'),
                    0 => __('Out of Stock'),
                ],
                'frame_callback' => [$this, 'callbackColumnIsInStock'],
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'align' => 'left',
                'type' => 'text',
                'width' => '120px',
                'sortable' => false,
                'filter' => false,
                'frame_callback' => [$this, 'callbackColumnType'],
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header' => __('Actions'),
                'align' => 'left',
                'type' => 'text',
                'width' => '125px',
                'filter' => false,
                'sortable' => false,
                'frame_callback' => [$this, 'callbackColumnActions'],
            ]
        );
    }

    // ----------------------------------------

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px">' . \M2E\Core\Helper\Data::escapeHtml($value);

        $tempSku = $row->getData('sku');
        if ($tempSku === null) {
            $tempSku = $this->ourMagentoProductFactory->createByProductId((int)$row->getData('entity_id'))
                                                      ->getSku();
        }

        $value .= '<br/><strong>' . __('SKU') . ':</strong> ';
        $value .= \M2E\Core\Helper\Data::escapeHtml($tempSku) . '</div>';

        return $value;
    }

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        return '<div style="margin-left: 3px">' . \M2E\Core\Helper\Data::escapeHtml(ucfirst($row->getTypeId())) . '</div>';
    }

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if ($row->getData('is_in_stock') === null) {
            return __('N/A');
        }

        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">' . __('Out of Stock') . '</span>';
        }

        return $value;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $url = $this->urlHelper->getUnmanagedMapUrl(
            [
                'product_id' => $row->getId(),
                'unmanaged_product_id' => $this->getData('unmanaged_product_id'),
                'account_id' => $this->getData('account_id'),
            ]
        );
        $actions = '<a href="javascript:void(0);" onclick="setLocation(\'' . $url . '\')">';
        $actions .= __('Link To This Product') . '</a>';

        return $actions;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
            ]
        );
    }

    // ----------------------------------------

    protected function _beforeToHtml()
    {
        $this->js->addOnReadyJs(
            <<<JS

        $$('#listingOtherMappingGrid div.grid th').each(function(el) {
            el.style.padding = '2px 4px';
        });

        $$('#listingOtherMappingGrid div.grid td').each(function(el) {
            el.style.padding = '2px 4px';
        });

         $$('.grid-listing-column-actions').each(function(el) {
            el.style.width = '200px';
        });

JS
        );

        return parent::_beforeToHtml();
    }

    // ----------------------------------------

    public function getGridUrl()
    {
        return $this->getUrl(
            $this->getData('grid_url'),
            [
                '_current' => true,
                'unmanaged_product_id' => $this->getData('unmanaged_product_id')
            ]
        );
    }

    public function getRowUrl($item)
    {
        return false;
    }

    // ----------------------------------------
}
