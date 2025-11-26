<?php

namespace M2E\Temu\Block\Adminhtml\Order;

use M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    private \M2E\Temu\Model\ResourceModel\Order\Note\Collection $notesCollection;
    private \M2E\Temu\Model\ResourceModel\Order\Item\Collection $itemsCollection;
    private \M2E\Temu\Block\Adminhtml\Order\StatusHelper $orderStatusHelper;
    private \M2E\Core\Helper\Url $urlHelper;
    private \M2E\Temu\Model\Currency $currency;
    private \M2E\Temu\Model\Order\Log\ServiceFactory $orderLogServiceFactory;
    private \M2E\Temu\Model\Order\Item\Repository $orderItemRepository;
    private \M2E\Temu\Model\Order\Repository $orderRepository;
    private \M2E\Temu\Model\Order\Note\Repository $noteRepository;

    public function __construct(
        \M2E\Temu\Model\Order\Item\Repository $orderItemRepository,
        \M2E\Temu\Model\Order\Log\ServiceFactory $orderLogServiceFactory,
        \M2E\Core\Helper\Url $urlHelper,
        \M2E\Temu\Block\Adminhtml\Order\StatusHelper $orderStatusHelper,
        \M2E\Temu\Model\Order\Repository $orderRepository,
        \M2E\Temu\Model\Order\Note\Repository $noteRepository,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \M2E\Temu\Model\Currency $currency,
        array $data = []
    ) {
        $this->orderStatusHelper = $orderStatusHelper;
        $this->urlHelper = $urlHelper;
        $this->currency = $currency;
        $this->orderLogServiceFactory = $orderLogServiceFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->noteRepository = $noteRepository;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('temuOrderGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort(\M2E\Temu\Model\ResourceModel\Order::COLUMN_PURCHASE_DATE);
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->orderRepository->getCollection(
            $this->getRequest()->getParam('account'),
            (bool)$this->getRequest()->getParam('not_created_only')
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $orderIds = $this->getCollection()->getColumnValues('id');

        $this->itemsCollection = $this->orderItemRepository->getOrderItemCollectionByOrderIds($orderIds);
        $this->notesCollection = $this->noteRepository->getOrderNoteCollectionByOrderIds($orderIds);

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'purchase_date',
            [
                'header' => __('Sale Date'),
                'align' => 'left',
                'type' => 'datetime',
                'filter' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'format' => \IntlDateFormatter::MEDIUM,
                'filter_time' => true,
                'index' => \M2E\Temu\Model\ResourceModel\Order::COLUMN_PURCHASE_DATE,
                'width' => '170px',
                'frame_callback' => [$this, 'callbackPurchaseDate'],
            ]
        );

        $this->addColumn(
            'shipping_date_to',
            [
                'header' => __('Ship By Date'),
                'align' => 'left',
                'type' => 'datetime',
                'filter' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'format' => \IntlDateFormatter::MEDIUM,
                'filter_time' => true,
                'index' => \M2E\Temu\Model\ResourceModel\Order::COLUMN_SHIP_BY_DATE,
                'width' => '170px',
                'frame_callback' => [$this, 'callbackShippingDateTo'],
            ]
        );

        $this->addColumn(
            'magento_order_num',
            [
                'header' => __('Magento Order #'),
                'align' => 'left',
                'index' => 'magento_order_num',
                'filter_index' => 'so.increment_id',
                'width' => '200px',
                'frame_callback' => [$this, 'callbackColumnMagentoOrder'],
            ]
        );

        $this->addColumn(
            'channel_order_id',
            [
                'header' => __(\M2E\Temu\Helper\Module::getChannelTitle() . ' Order #'),
                'align' => 'left',
                'width' => '145px',
                'index' => 'channel_order_id',
                'frame_callback' => [$this, 'callbackColumnTemuOrder'],
                'filter' => \M2E\Temu\Block\Adminhtml\Order\Grid\Column\Filter\OrderId::class,
                'filter_condition_callback' => [$this, 'callbackFilterTemuOrderId'],
            ]
        );

        $this->addColumn(
            'order_items',
            [
                'header' => __('Items'),
                'align' => 'left',
                'index' => 'order_items',
                'sortable' => false,
                'width' => '*',
                'frame_callback' => [$this, 'callbackColumnItems'],
                'filter_condition_callback' => [$this, 'callbackFilterItems'],
            ]
        );

        $this->addColumn(
            'buyer',
            [
                'header' => __('Buyer'),
                'align' => 'left',
                'index' => 'buyer_name',
                'frame_callback' => [$this, 'callbackColumnBuyer'],
                'filter_condition_callback' => [$this, 'callbackFilterBuyer'],
                'width' => '120px',
            ]
        );

        $this->addColumn(
            'price_total',
            [
                'header' => __('Total Paid'),
                'align' => 'left',
                'width' => '110px',
                'type' => 'number',
                'index' => 'price_total',
                'filter_index' => '(price_total + price_delivery + JSON_UNQUOTE(JSON_EXTRACT(tax_details, "$.amount")))',
                'frame_callback' => [$this, 'callbackColumnTotal'],
                'filter_condition_callback' => [$this, 'callbackFilterPriceTotal'],
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'status',
                'type' => 'options',
                'options' => $this->orderStatusHelper->getStatusesOptions(),
                'frame_callback' => [$this, 'callbackColumnStatus'],
                'filter_condition_callback' => [$this, 'callbackFilterStatus'],
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $groups = [
            'general' => __('General'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem(
            'reservation_place',
            [
                'label' => __('Reserve QTY'),
                'url' => $this->getUrl('*/order/reservationPlace'),
                'confirm' => __('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'reservation_cancel',
            [
                'label' => __('Cancel QTY Reserve'),
                'url' => $this->getUrl('*/order/reservationCancel'),
                'confirm' => __('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'resend_shipping',
            [
                'label' => __('Resend Shipping Information'),
                'url' => $this->getUrl('*/order/resubmitShippingInfo'),
                'confirm' => __('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'create_order',
            [
                'label' => __('Create Magento Order'),
                'url' => $this->getUrl('*/order/CreateMagentoOrder'),
                'confirm' => __('Are you sure?'),
            ],
            'general'
        );

        return parent::_prepareMassaction();
    }

    public function callbackColumnMagentoOrder($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row->getData('magento_order_id');
        $returnString = __('N/A');

        if ($magentoOrderId !== null) {
            if (!empty($value)) {
                $magentoOrderNumber = \M2E\Core\Helper\Data::escapeHtml($value);
                $orderUrl = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
                $returnString = '<a href="' . $orderUrl . '" target="_blank">' . $magentoOrderNumber . '</a>';
            } else {
                $returnString = '<span style="color: red;">' . __('Deleted') . '</span>';
            }
        }

        /** @var \M2E\Temu\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order $viewLogIcon */
        $viewLogIcon = $this
            ->getLayout()
            ->createBlock(\M2E\Temu\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order::class);
        $logIconHtml = $viewLogIcon->render($row);

        if ($logIconHtml !== '') {
            return '<div style="min-width: 100px">' . $returnString . $logIconHtml . '</div>';
        }

        return $returnString;
    }

    public function callbackPurchaseDate($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getPurchaseDate(),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackShippingDateTo($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getShipByDate(),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackColumnTemuOrder($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        $back = $this->urlHelper->makeBackUrlParam('*/order/index');
        $itemUrl = $this->getUrl('*/order/view', ['id' => $row->getId(), 'back' => $back]);

        $returnString = sprintf('<a href="%s">%s</a>', $itemUrl, $row->getChannelOrderId());

        /** @var \M2E\Temu\Model\Order\Note[] $notes */
        $notes = $this->notesCollection->getItemsByColumnValue('order_id', $row->getId());
        $returnString .= $this->formatNotes($notes);

        return $returnString;
    }

    /**
     * @param string $text
     * @param int $maxLength
     *
     * @return string
     */
    private function cutText(string $text, int $maxLength): string
    {
        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength) . "..." : $text;
    }

    /**
     * @param $notes
     *
     * @return string
     */
    private function formatNotes($notes)
    {
        $notesHtml = '';
        $maxLength = 250;

        if (!$notes) {
            return '';
        }

        $notesHtml .= <<<HTML
    <div class="note_icon admin__field-tooltip">
        <a class="admin__field-tooltip-note-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="right: -4.4rem">
            <div class="tts-identifiers">
HTML;

        if (count($notes) === 1) {
            $noteValue = $notes[0]->getNote();
            $shortenedNote = $this->cutText($noteValue, $maxLength);
            $notesHtml .= "<div>{$shortenedNote}</div>";
        } else {
            $notesHtml .= "<ul>";
            foreach ($notes as $note) {
                $noteValue = $note->getNote();
                $shortenedNote = $this->cutText($noteValue, $maxLength);
                $notesHtml .= "<li>{$shortenedNote}</li>";
            }
            $notesHtml .= "</ul>";
        }

        $notesHtml .= <<<HTML
            </div>
        </div>
    </div>
HTML;

        return $notesHtml;
    }

    public function callbackColumnItems($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        $itemsCollection = $this->orderItemRepository->getOrderItemCollection((int)$row->getId());
        $items = $itemsCollection->getItems();

        $itemLines = [];
        foreach ($items as $item) {
            try {
                $product = $item->getProduct();
            } catch (\M2E\Temu\Model\Exception $e) {
                $product = null;
                $logService = $this->orderLogServiceFactory->create();

                $logService->addMessage(
                    $row,
                    $e->getMessage(),
                    \M2E\Temu\Model\Log\AbstractModel::TYPE_ERROR
                );
            }

            $skuHtml = '';
            if ($item->getProductSku()) {
                $sku = \M2E\Core\Helper\Data::escapeHtml($item->getProductSku());
                if ($product !== null) {
                    $sku = sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        $this->getUrl('catalog/product/edit', ['id' => $product->getId()]),
                        $sku
                    );
                }

                $skuHtml = sprintf(
                    '<span style="padding-left: 10px;"><b>%s:</b>&nbsp;%s</span><br/>',
                    __('SKU'),
                    $sku
                );
            }

            $canceledLabel = '';
            if ($item->isStatusCanceled()) {
                $canceledLabel = sprintf(
                    '&nbsp;<span style="color: %s">%s</span>',
                    $this->orderStatusHelper->getStatusColor($item->getStatus()),
                    $this->orderStatusHelper->getStatusLabel($item->getStatus())
                );
            }

            $qtyPurchasedHtml = sprintf(
                '<span style="padding-left: 10px;"><b>%s:</b>&nbsp;%d</span>%s<br/>',
                __('QTY'),
                $item->getQty(),
                $canceledLabel
            );

            $opcHtml = sprintf(
                '<span style="padding-left: 10px;"><b>%s:</b>&nbsp;%s</span><br/>',
                __('Goods ID'),
                $item->getChannelProductId()
            );

            $itemLines[] = sprintf(
                '%s<small>%s%s</small>',
                $skuHtml,
                $opcHtml,
                $qtyPurchasedHtml
            );
        }

        $html = '';

        return $html . implode('<br>', $itemLines);
    }

    public function callbackColumnBuyer($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        $returnString = \M2E\Core\Helper\Data::escapeHtml($row->getBuyerName()) . '<br/>';
        $returnString .= \M2E\Core\Helper\Data::escapeHtml($row->getBuyerUserId());

        return $returnString;
    }

    public function callbackColumnTotal($value, \M2E\Temu\Model\Order $row, $column, $isExport)
    {
        return $this->currency->formatPrice($row->getCurrency(), $row->getGrandTotal());
    }

    public function callbackColumnStatus($value, \M2E\Temu\Model\Order $row, $column, $isExport): string
    {
        $status = $row->getStatus();

        return sprintf(
            '<span style="color: %s">%s</span>',
            $this->orderStatusHelper->getStatusColor($status),
            $this->orderStatusHelper->getStatusLabel($status),
        );
    }

    protected function callbackFilterTemuOrderId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if (empty($value)) {
            return;
        }

        if (!empty($value['value'])) {
            $collection->getSelect()->where('channel_order_id LIKE ?', "%{$value['value']}%");
        }
    }

    protected function callbackFilterItems($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $orderIds = $this->orderItemRepository->getOrderIdsBySearchValue($value);

        if (empty($orderIds)) {
            $collection->addFieldToFilter('main_table.id', ['in' => [0]]);

            return;
        }

        $collection->addFieldToFilter('main_table.id', ['in' => $orderIds]);
    }

    protected function callbackFilterBuyer($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection
            ->getSelect()
            ->where('buyer_email LIKE ? OR buyer_name LIKE ?', '%' . $value . '%');
    }

    protected function callbackFilterPriceTotal($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if (isset($value['from'])) {
            $conditionFrom = '(price_total + price_delivery + JSON_UNQUOTE(JSON_EXTRACT(tax_details, "$.amount"))) >= ?';
            $collection->getSelect()->where($conditionFrom, $value['from']);
        }

        if (isset($value['to'])) {
            $conditionTo = '(price_total + price_delivery + JSON_UNQUOTE(JSON_EXTRACT(tax_details, "$.amount"))) <= ?';
            $collection->getSelect()->where($conditionTo, $value['to']);
        }
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter('order_status', ['eq' => $value]);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/order/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
                OrderObj.initializeGrids();
JS
            );

            return parent::_toHtml();
        }

        $tempGridIds = [];
        $tempGridIds[] = $this->getId();
        $tempGridIds = \M2E\Core\Helper\Json::encode($tempGridIds);

        $this->jsPhp->addConstants(
            [
                '\M2E\Temu\Model\Log\AbstractModel::TYPE_INFO' => \M2E\Temu\Model\Log\AbstractModel::TYPE_INFO,
                '\M2E\Temu\Model\Log\AbstractModel::TYPE_SUCCESS' => \M2E\Temu\Model\Log\AbstractModel::TYPE_SUCCESS,
                '\M2E\Temu\Model\Log\AbstractModel::TYPE_WARNING' => \M2E\Temu\Model\Log\AbstractModel::TYPE_WARNING,
                '\M2E\Temu\Model\Log\AbstractModel::TYPE_ERROR' => \M2E\Temu\Model\Log\AbstractModel::TYPE_ERROR,
            ]
        );

        $this->jsTranslator->add('View Full Order Log', __('View Full Order Log'));

        $this->js->add(
            <<<JS
    require([
        'Temu/Order'
    ], function(){
        window.OrderObj = new Order('$tempGridIds');
        OrderObj.initializeGrids();
    });
JS
        );

        return parent::_toHtml();
    }
}
