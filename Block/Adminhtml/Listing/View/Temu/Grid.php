<?php

namespace M2E\Temu\Block\Adminhtml\Listing\View\Temu;

use M2E\Temu\Block\Adminhtml\Grid\Column\Renderer\ChannelProductId;
use M2E\Temu\Block\Adminhtml\Log\AbstractGrid;
use M2E\Temu\Model\Product;
use M2E\Temu\Model\ResourceModel\Product as ListingProductResource;

class Grid extends \M2E\Temu\Block\Adminhtml\Listing\View\AbstractGrid
{
    private const COLUMN_INDEX_VARIANTS_PRICE = 'variants_price';

    private \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\Temu\Helper\Data\Session $sessionDataHelper;
    private \M2E\Temu\Model\Currency $currency;
    private ListingProductResource $listingProductResource;
    private \M2E\Core\Helper\Url $urlHelper;
    private \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory;
    private \M2E\Temu\Model\Product\Repository $productRepository;

    public function __construct(
        \M2E\Temu\Model\Product\Repository $productRepository,
        ListingProductResource $listingProductResource,
        \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory,
        \M2E\Temu\Helper\Data\Session $sessionDataHelper,
        \M2E\Temu\Model\Listing\Ui\RuntimeStorage $uiListingRuntimeStorage,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \M2E\Temu\Helper\Data $dataHelper,
        \M2E\Core\Helper\Url $urlHelper,
        \M2E\Temu\Helper\Data\GlobalData $globalDataHelper,
        \M2E\Temu\Model\Currency $currency,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->sessionDataHelper = $sessionDataHelper;
        $this->currency = $currency;
        $this->listingProductResource = $listingProductResource;
        $this->urlHelper = $urlHelper;
        $this->ourMagentoProductFactory = $ourMagentoProductFactory;
        parent::__construct(
            $uiListingRuntimeStorage,
            $context,
            $backendHelper,
            $dataHelper,
            $globalDataHelper,
            $sessionDataHelper,
            $data
        );
        $this->productRepository = $productRepository;
    }

    public function _construct(): void
    {
        parent::_construct();

        $this->setDefaultSort(false);

        $this->setId('temuListingViewGrid' . $this->getListing()->getId());

        $this->showAdvancedFilterProductsOption = false;
    }

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return $this;
        }

        $columnIndex = $column->getFilterIndex() ?: $column->getIndex();

        if ($columnIndex === self::COLUMN_INDEX_VARIANTS_PRICE) {
            if ($column->getDir() === 'asc') {
                $collection->getSelect()->order('online_min_price ASC');
            } else {
                $collection->getSelect()->order('online_max_price DESC');
            }

            return $this;
        }

        $collection->getSelect()->order($columnIndex . ' ' . strtoupper($column->getDir()));

        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setItemObjectClass(Row::class);
        $collection->setListingProductModeOn();
        $collection->setStoreId($this->getListing()->getStoreId());

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            ['lp' => $this->listingProductResource->getMainTable()],
            sprintf('%s = entity_id', ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID),
            [
                Row::KEY_LISTING_PRODUCT_ID => ListingProductResource::COLUMN_ID,
                'status' => ListingProductResource::COLUMN_STATUS,
                'product_id' => ListingProductResource::COLUMN_CHANNEL_PRODUCT_ID,
                'additional_data' => ListingProductResource::COLUMN_ADDITIONAL_DATA,
                'online_title' => ListingProductResource::COLUMN_ONLINE_TITLE,
                'online_qty' => ListingProductResource::COLUMN_ONLINE_QTY,
                'online_sku' => ListingProductResource::COLUMN_ONLINE_SKU,
                'online_min_price' => ListingProductResource::COLUMN_ONLINE_MIN_PRICE,
                'online_max_price' => ListingProductResource::COLUMN_ONLINE_MAX_PRICE,
                'listing_id' => ListingProductResource::COLUMN_LISTING_ID,
                'variation_attributes' => ListingProductResource::COLUMN_VARIATION_ATTRIBUTES,
                'is_valid_category_attributes' => ListingProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
            ],
            sprintf(
                '{{table}}.%s = %s',
                ListingProductResource::COLUMN_LISTING_ID,
                $this->getListing()->getId()
            )
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addExportType('*/*/exportCsvListingGrid', __('CSV'));

        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'store_id' => $this->getListing()->getStoreId(),
            'renderer' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'header_export' => __('Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'online_title',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('channel_product_id', [
            'header' => __('Goods ID'),
            'align' => 'left',
            'width' => '100',
            'type' => 'text',
            'index' => 'product_id',
            'renderer' => ChannelProductId::class,
        ]);

        $this->addColumn(
            'online_qty',
            [
                'header' => __('Available QTY'),
                'align' => 'right',
                'width' => '50px',
                'type' => 'number',
                'index' => 'online_qty',
                'sortable' => true,
                'filter_index' => 'online_qty',
                'renderer' => \M2E\Temu\Block\Adminhtml\Grid\Column\Renderer\OnlineQty::class,
            ]
        );

        $priceColumn = [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => self::COLUMN_INDEX_VARIANTS_PRICE,
            'sortable' => true,
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ];

        $priceColumn['filter'] = \M2E\Temu\Block\Adminhtml\Grid\Column\Filter\Price::class;

        $this->addColumn('price', $priceColumn);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                Product::STATUS_NOT_LISTED => Product::getStatusTitle(Product::STATUS_NOT_LISTED),
                Product::STATUS_LISTED => Product::getStatusTitle(Product::STATUS_LISTED),
                Product::STATUS_INACTIVE => Product::getStatusTitle(Product::STATUS_INACTIVE),
                Product::STATUS_BLOCKED => Product::getStatusTitle(Product::STATUS_BLOCKED),
            ],
            'showLogIcon' => true,
            'renderer' => \M2E\Temu\Block\Adminhtml\Grid\Column\Renderer\Status::class,
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        $this->addColumn('status', $statusColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField(Row::KEY_LISTING_PRODUCT_ID);
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Configure groups
        // ---------------------------------------

        $groups = [
            'actions' => __('Listing Actions'),
            'other' => __('Other'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------

        $this->getMassactionBlock()->addItem('list', [
            'label' => __('List Item(s) on ' . \M2E\Temu\Helper\Module::getChannelTitle()),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label' => __('Revise Item(s) on ' . \M2E\Temu\Helper\Module::getChannelTitle()),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label' => __('Relist Item(s) on ' . \M2E\Temu\Helper\Module::getChannelTitle()),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label' => __('Stop Item(s) on ' . \M2E\Temu\Helper\Module::getChannelTitle()),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => __(
                'Stop on %channel_title / Remove from Listing',
                [
                    'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                ]
            ),
            'url' => '',
        ], 'actions');

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _afterLoadCollection()
    {
        /** @var Row[] $items */
        $items = $this->getCollection()->getItems();

        $listingProductIds = [];
        foreach ($items as $item) {
            $listingProductIds[] = $item->getListingProductId();
        }

        $products = $this->productRepository->findByIds($listingProductIds);

        $sortedProductsById = [];
        foreach ($products as $product) {
            $sortedProductsById[$product->getId()] = $product;
        }

        foreach ($items as $item) {
            $item->setListingProduct($sortedProductsById[$item->getListingProductId()] ?? null);
        }

        return parent::_afterLoadCollection();
    }

    /**
     * @param $value
     * @param \M2E\Temu\Block\Adminhtml\Listing\View\Temu\Row $row
     * @param $column
     * @param $isExport
     *
     * @return array|string|string[]|null
     */
    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $row->getName();

        $onlineTitle = $row->getData('online_title');
        if (!empty($onlineTitle)) {
            $title = $onlineTitle;
        }

        $title = \M2E\Core\Helper\Data::escapeHtml($title);

        $valueHtml = '<span class="product-title-value">' . $title . '</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->ourMagentoProductFactory
                ->create()
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        if ($isExport) {
            return \M2E\Core\Helper\Data::escapeHtml($sku);
        }

        $valueHtml .= '<br/>' .
            '<strong>' . __('SKU') . ':</strong>&nbsp;' .
            \M2E\Core\Helper\Data::escapeHtml($sku);

        $listingProduct = $row->getListingProduct();

        if ($listingProduct === null || $listingProduct->isSimple()) {
            return $valueHtml;
        }

        $variationAttributes = $listingProduct->getVariationAttributes();

        if ($variationAttributes->isEmpty()) {
            return $valueHtml;
        }

        $configurableAttributes = array_map(
            function (\M2E\Temu\Model\Product\Dto\VariationAttributeItem $item) {
                return sprintf('<span>%s</span>', $item->getName());
            },
            $variationAttributes->getItems()
        );

        $onclick = sprintf(
            'TemuListingVariationProductManageObj.openPopUp(%s, \'%s\')',
            $listingProduct->getId(),
            $this->_escaper->escapeJs($row->getName())
        );

        $manageLinkHtml = sprintf(
            '<a href="javascript:;" onclick="%s">%s</a>',
            $onclick,
            $this->__('Manage Variations')
        );

        $valueHtml .= sprintf(
            '<div class="m2e-salable-attribute-list"><p class="m2e-list">%s</p><p>%s</p></div>',
            implode(', ', $configurableAttributes),
            $manageLinkHtml
        );

        return $valueHtml;
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
                ['attribute' => 'online_title', 'like' => '%' . $value . '%'],
                ['attribute' => 'online_sku', 'like' => '%' . $value . '%'],
            ]
        );
    }

    /**
     * @param $value
     * @param Row $row
     * @param $column
     * @param $isExport
     *
     * @return mixed|string
     */
    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return (string)$value;
        }

        $productStatus = $row->getData('status');

        if ((int)$productStatus === \M2E\Temu\Model\Product::STATUS_NOT_LISTED) {
            return sprintf(
                '<span style="color: gray;">%s</span>',
                __('Not Listed')
            );
        }

        $minPrice = $row->getData('online_min_price');
        $maxPrice = $row->getData('online_max_price');

        if ($minPrice === $maxPrice) {
            $price = $this->currency->formatPrice(
                $this->getListing()->getAccount()->getCurrencyCode(),
                (float)$minPrice
            );

            return $price;
        }

        $formattedMinPrice = $this->currency->formatPrice(
            $this->getListing()->getAccount()->getCurrencyCode(),
            (float)$minPrice
        );

        $formattedMaxPrice = $this->currency->formatPrice(
            $this->getListing()->getAccount()->getCurrencyCode(),
            (float)$maxPrice
        );

        return sprintf('%s - %s', $formattedMinPrice, $formattedMaxPrice);
    }

    /**
     * @param \M2E\Core\Model\ResourceModel\Magento\Product\Collection $collection
     * @param $column
     *
     * @return void
     */
    protected function callbackFilterPrice($collection, $column)
    {
        $condition = $column->getFilter()->getCondition();
        if (empty($condition)) {
            return;
        }

        $from = $condition['from'] ?? null;
        $to = $condition['to'] ?? null;

        $whereConditions = [];

        if (!is_numeric($from)) {
            $from = PHP_INT_MIN;
        }
        if (!is_numeric($to)) {
            $to = PHP_INT_MAX;
        }

        $whereConditions[] = sprintf('%s <= online_max_price AND online_max_price <= %s', $from, $to);
        $whereConditions[] = sprintf('%s <= online_min_price AND online_min_price <= %s', $from, $to);
        $whereConditions[] = sprintf('online_min_price <= %s AND %s <= online_max_price', $from, $to);

        $collection->getSelect()->where('(' . implode(' OR ', $whereConditions) . ')');

        $this->setCollection($collection);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null) {
            return;
        }

        if (is_array($value) && isset($value['value'])) {
            $collection->addFieldToFilter($index, (int)$value['value']);
        } else {
            if (!is_array($value) && $value !== null) {
                $collection->addFieldToFilter($index, (int)$value);
            }
        }
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/listing/view', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getTooltipHtml(string $content, $id = false): string
    {
        return <<<HTML
<div id="$id" class="Temu-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content" style="">
        {$content}
    </div>
</div>
HTML;
    }

    protected function _beforeToHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add("TemuListingViewTemuGridObj.afterInitPage()");

            return parent::_beforeToHtml();
        }

        $temp = $this->sessionDataHelper->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $gridId = $this->getId();
        $ignoreListings = \M2E\Core\Helper\Json::encode([$this->getListing()->getId()]);

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/listing/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/listing/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/listing/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/listing/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/listing/runStopAndRemoveProducts'),
            'previewItems' => $this->getUrl('*/listing/previewItems'),
        ]);

        $this->jsUrl->add(
            $this->getUrl('*/log_listing_product/index'),
            'log_listing_product/index'
        );

        $this->jsUrl->add(
            $this->getUrl('*/log_listing_product/index', [
                AbstractGrid::LISTING_ID_FIELD => $this->getListing()->getId(),
                'back' => $this->urlHelper->makeBackUrlParam(
                    '*/listing/view',
                    ['id' => $this->getListing()->getId()]
                ),
            ]),
            'logViewUrl'
        );
        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->add(
            $this->getUrl('*/listing_moving/moveToListingGrid'),
            'listing_moving/moveToListingGrid'
        );

        $taskCompletedWarningMessage = __(
            '"%task_title%" task has completed with warnings. ' .
            '<a target="_blank" href="%url%">View Log</a> for details.'
        );

        $taskCompletedErrorMessage = __(
            '"%task_title%" task has completed with errors. ' .
            '<a target="_blank" href="%url%">View Log</a> for details.'
        );

        $channelTitle = \M2E\Temu\Helper\Module::getChannelTitle();

        $this->jsTranslator->addTranslations([
            'task_completed_message' => __('Task completed. Please wait ...'),
            'task_completed_success_message' => __('"%task_title%" task has completed.'),
            'task_completed_warning_message' => $taskCompletedWarningMessage,
            'task_completed_error_message' => $taskCompletedErrorMessage,
            'sending_data_message' => __(
                'Sending %product_title% Product(s) data on %channel_title.',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'view_full_product_log' => __('View Full Product Log.'),
            'listing_selected_items_message' => __(
                'Listing Selected Items On %channel_title',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'revising_selected_items_message' => __(
                'Revising Selected Items On %channel_title',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'relisting_selected_items_message' => __(
                'Relisting Selected Items On %channel_title',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'stopping_selected_items_message' => __(
                'Stopping Selected Items On %channel_title',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'stopping_and_removing_selected_items_message' => __(
                'Removing from %channel_title And Removing From Listing Selected Items',
                [
                    'channel_title' => $channelTitle,
                ]
            ),
            'removing_selected_items_message' => __('Removing From Listing Selected Items'),

            'Please select the Products you want to perform the Action on.' =>
                __('Please select the Products you want to perform the Action on.'),
            'Please select Action.' => __('Please select Action.'),
            'Specifics' => __('Specifics'),
        ]);

        $this->js->add(
            <<<JS
    Temu.productsIdsForList = '$productsIdsForList';
    Temu.customData.gridId = '$gridId';
    Temu.customData.ignoreListings = '$ignoreListings';
JS
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'Temu/Listing/View/Temu/Grid',
        'Temu/Listing/VariationProductManage'
    ], function() {
        window.TemuListingVariationProductManageObj = new TemuListingVariationProductManage()
        window.TemuListingViewTemuGridObj = new TemuListingViewTemuGrid('$gridId', {$this->getListing()->getId()});

        TemuListingViewTemuGridObj.afterInitPage();

        TemuListingViewTemuGridObj.actionHandler.setProgressBar('listing_view_progress_bar');
        TemuListingViewTemuGridObj.actionHandler.setGridWrapper('listing_view_content_container');

        if (Temu.productsIdsForList) {
            TemuListingViewTemuGridObj.getGridMassActionObj().checkedString = Temu.productsIdsForList;
            TemuListingViewTemuGridObj.actionHandler.listAction();
        }
    });
JS
        );

        return parent::_beforeToHtml();
    }
}
