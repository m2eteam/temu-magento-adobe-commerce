<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Listing\Variation\Product\Manage\View;

use M2E\Temu\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite as Column;
use M2E\Temu\Model\Product;

class Grid extends \M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Magento\Framework\Locale\CurrencyInterface $localeCurrency;
    private Product $listingProduct;
    private CollectionFactory $unionCollection;
    private \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;

    public function __construct(
        Product $listingProduct,
        CollectionFactory $unionCollection,
        \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->localeCurrency = $localeCurrency;
        $this->listingProduct = $listingProduct;
        $this->unionCollection = $unionCollection;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
    }

    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();

        $this->setId('temuVariationProductManageGrid');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->unionCollection->create();

        $collection->addFieldToFilter(
            GridRow::COLUMN_PRODUCT_ID,
            ['eq' => $this->listingProduct->getId()]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection(): Grid
    {
        $magentoProductCollection = $this->magentoProductCollectionFactory->create();
        $magentoProductCollection->addAttributeToSelect('sku');

        /** @var GridRow $item */
        foreach ($this->getCollection()->getItems() as $item) {
            $magentoProductId = $item->getMagentoProductId();
            if ($magentoProductId === null) {
                continue;
            }

            /** @var \Magento\Catalog\Model\Product $magentoProduct */
            $magentoProduct = $magentoProductCollection->getItemById($magentoProductId);
            if (empty($magentoProduct)) {
                continue;
            }

            $item->setMagentoProduct($magentoProduct);
        }

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('variation', [
            'header' => __('Variation'),
            'align' => 'left',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnVariation'],
        ]);

        $this->addColumn('sku', [
            'header' => __('SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => GridRow::COLUMN_SKU,
            'filter_index' => GridRow::COLUMN_SKU,
            'frame_callback' => [$this, 'callbackColumnSku'],
            'filter_condition_callback' => [$this, 'callbackFilterSku'],
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('Available QTY'),
            'align' => 'right',
            'type' => 'number',
            'index' => GridRow::COLUMN_ONLINE_QTY,
            'filter_index' => GridRow::COLUMN_ONLINE_QTY,
            'frame_callback' => [$this, 'callbackColumnQty'],
        ]);

        $this->addColumn('price', [
            'header' => $this->__('Price'),
            'type' => 'number',
            'index' => GridRow::COLUMN_ONLINE_PRICE,
            'filter_index' => GridRow::COLUMN_ONLINE_PRICE,
            'frame_callback' => [$this, 'callbackColumnPrice'],
        ]);

        $this->addColumn('variant_status', [
            'header' => $this->__('Status'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'options',
            'index' => 'status',
            'filter_index' => 'status',
            'options' => [
                Product::STATUS_NOT_LISTED => Product::getStatusTitle(Product::STATUS_NOT_LISTED),
                Product::STATUS_LISTED => Product::getStatusTitle(Product::STATUS_LISTED),
                Product::STATUS_INACTIVE => Product::getStatusTitle(Product::STATUS_INACTIVE),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnVariation(string $value, GridRow $row, Column $column, bool $isExport): string
    {
        $variationList = array_map(function ($item) {
            return sprintf(
                '<p><strong>%s</strong>: %s</p>',
                $item->getAttributeName() ?: '--',
                $item->getValue() ?: '--'
            );
        }, $row->getVariationData()->getItems());

        if (!$row->hasMagentoProductId()) {
            $label = sprintf('<p><span class="deleted-attribute">%s</span></p>', __('Not Linked'));
            return sprintf(
                '<div class="m2e-variation-attributes">%s%s</div>',
                implode('', $variationList),
                $label
            );
        }

        $magentoProductUrl = $this->getUrl(
            'catalog/product/edit',
            [
                'id' => $row->getMagentoProductId(),
                'store' => $this->listingProduct->getListing()->getStoreId(),
            ]
        );

        return sprintf(
            '<div class="m2e-variation-attributes"><a href="%s" target="_blank">%s</a></div>',
            $magentoProductUrl,
            implode('', $variationList)
        );
    }

    public function callbackColumnSku(string $value, GridRow $row, Column $column, bool $isExport): string
    {
        $onlineSku = $row->getOnlineSku();
        if (!empty($onlineSku)) {
            return $onlineSku;
        }

        if ($row->isVariationDeleted()) {
            return sprintf('<span style="color:gray">%s</span>', __('N/A'));
        }

        return $value;
    }

    public function callbackColumnQty(?string $value, GridRow $row, Column $column, bool $isExport): string
    {
        if ($row->isVariationDeleted()) {
            return sprintf('<span style="color:gray">%s</span>', __('N/A'));
        }

        if ($row->isStatusNodListed()) {
            return sprintf('<span style="color: gray;">%s</span>', __('Not Listed'));
        }

        if (empty($value)) {
            return '0';
        }

        return $value;
    }

    public function callbackColumnPrice(?string $value, GridRow $row, Column $column, bool $isExport): string
    {
        if ($row->isStatusNodListed()) {
            return sprintf('<span style="color: gray;">%s</span>', __('Not Listed'));
        }

        if (empty($value)) {
            return sprintf('<span style="color:gray">%s</span>', __('N/A'));
        }

        if ($value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $this->listingProduct->getCurrencyCode();

        return $this->localeCurrency->getCurrency($currency)->toCurrency($value);
    }

    public function callbackColumnStatus(?string $value, GridRow $row, Column $column, bool $isExport): string
    {
        $colors = [
            Product::STATUS_NOT_LISTED => 'gray',
            Product::STATUS_LISTED => 'green',
            Product::STATUS_INACTIVE => 'red',
        ];

        return sprintf(
            '<span style="color: %s">%s</span>',
            $colors[$row->getStatus()] ?? 'gray',
            $value
        );
    }

    protected function callbackFilterSku(Collection $collection, Column $column)
    {
        $filterValue = $column->getFilter()->getValue();
        if ($filterValue) {
            $collection
                ->getSelect()
                ->where(
                    sprintf(
                        '%s LIKE ? OR %s LIKE ?',
                        GridRow::COLUMN_SKU,
                        GridRow::COLUMN_ONLINE_SKU,
                    ),
                    "%$filterValue%"
                );
        }

        return null;
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/listing_variation_product_manage/getGridHtml', [
            'product_id' => $this->listingProduct->getId(),
            '_current' => true,
        ]);
    }

    public function getRowUrl($item)
    {
        return false;
    }
}
