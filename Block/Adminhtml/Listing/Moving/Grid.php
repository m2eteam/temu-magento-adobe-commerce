<?php

namespace M2E\Temu\Block\Adminhtml\Listing\Moving;

use M2E\Temu\Model\ResourceModel\UnmanagedProduct as UnmanagedProductResource;

class Grid extends \M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private int $accountId;
    private \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;
    private \Magento\Store\Model\StoreFactory $storeFactory;
    private \M2E\Temu\Helper\Data\GlobalData $globalDataHelper;
    private \M2E\Temu\Model\Listing\ProductTotalsSelect $productTotalsSelect;

    public function __construct(
        int $accountId,
        \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \M2E\Temu\Helper\Data\GlobalData $globalDataHelper,
        \M2E\Temu\Model\Listing\ProductTotalsSelect $productTotalsSelect,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->accountId = $accountId;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->storeFactory = $storeFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->productTotalsSelect = $productTotalsSelect;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingMovingGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->listingCollectionFactory->create();

        $collection->addFieldToFilter(UnmanagedProductResource::COLUMN_ACCOUNT_ID, $this->accountId);
        $collection->joinLeft(
            ['t' => $this->productTotalsSelect->getSelect()],
            'main_table.id = t.listing_id',
            [
                'products_total_count' => 'products_total_count',
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('listing_id', [
            'header' => __('ID'),
            'align' => 'right',
            'type' => 'number',
            'width' => '75px',
            'index' => 'id',
            'filter_index' => 'id',
        ]);

        $this->addColumn('title', [
            'header' => __('Title'),
            'align' => 'left',
            'type' => 'text',
            'width' => '200px',
            'index' => 'title',
            'escape' => false,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
        ]);

        $this->addColumn('store_name', [
            'header' => __('Store View'),
            'align' => 'left',
            'type' => 'text',
            'width' => '100px',
            'index' => 'store_id',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnStore'],
        ]);

        $this->addColumn('products_total_count', [
            'header' => __('Total Items'),
            'align' => 'right',
            'type' => 'number',
            'width' => '100px',
            'index' => 'products_total_count',
            'filter_index' => 'products_total_count',
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'left',
            'type' => 'text',
            'width' => '125px',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = \M2E\Core\Helper\Data::escapeHtml($value);
        $url = $this->getUrl('*/listing/view', ['id' => $row->getData('id')]);

        return sprintf('<a href="%s" target="_blank">%s</a>', $url, $title);
    }

    public function callbackColumnStore($value, $row, $column, $isExport)
    {
        $storeModel = $this->storeFactory->create()->load($value);
        $website = $storeModel->getWebsite();

        if (!$website) {
            return '';
        }

        $websiteName = $website->getName();

        if (strtolower($websiteName) != 'admin') {
            $storeName = $storeModel->getName();
        } else {
            $storeName = $storeModel->getGroup()->getName();
        }

        return $storeName;
    }

    public function callbackColumnSource($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $listingId = $row->getData('id');
        $moveText = __('Move To This Listing');
        $url = $this->getUrl('m2e_temu/listing_wizard/createUnmanaged', ['listingId' => $listingId]);

        return <<<HTML
&nbsp;<a href="javascript:void(0);" onclick="window.location.href='{$url}';">$moveText</a>
HTML;
    }

    protected function getHelpBlockHtml()
    {
        $helpBlockHtml = '';

        if ($this->canDisplayContainer()) {
            $helpBlockHtml = $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\HelpBlock::class)->setData(
                [
                    'content' => __(
                        'Item(s) can be moved to a Listing within the same %channel_title Account.<br> ' .
                        'You can select an existing %extension_title Listing or create a new one.<br><br>' .
                        '<strong>Note:</strong> Once the Items are moved, they will be updated based ' .
                        'on the new Listing settings.',
                        [
                            'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                            'extension_title' => \M2E\Temu\Helper\Module::getExtensionTitle(),
                        ]
                    ),
                ]
            )->toHtml();
        }

        return $helpBlockHtml;
    }

    protected function getNewListingUrl(): string
    {
        return $this->getUrl(
            '*/listing_create/index',
            [
                'step' => 1,
                'clear' => 1,
                'account_id' => $this->globalDataHelper->getValue('accountId'),
                'creation_mode' => \M2E\Temu\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY,
            ]
        );
    }

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewListingUrl(), 'add_new_listing_url');

        $this->js->add(
            <<<JS
        var warning_msg_block = $('empty_grid_warning');
            warning_msg_block && warning_msg_block.remove();

            $$('#listingMovingGrid div.grid th').each(function(el) {
                el.style.padding = '2px 4px';
            });

            $$('#listingMovingGrid div.grid td').each(function(el) {
                el.style.padding = '2px 4px';
            });
JS
        );

        return $this->getHelpBlockHtml() . parent::_toHtml();
    }

    public function getGridUrl()
    {
        return $this->getData('grid_url');
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function addAccountFilter($collection): void
    {
        $accountId = $this->globalDataHelper->getValue('accountId');

        $collection->addFieldToFilter('account_id', $accountId);
    }
}
