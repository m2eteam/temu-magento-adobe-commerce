<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Category\Attributes\Validation;

use M2E\Temu\Model\ResourceModel\Product as ProductResource;
use M2E\Temu\Model\ResourceModel\Category\Dictionary as CategoryDictionaryResource;

class Grid extends \M2E\Temu\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private int $templateCategoryId;
    private \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory;
    private \M2E\Temu\Model\ResourceModel\Product $productResource;
    private \M2E\Temu\Model\ResourceModel\Category\Dictionary $categoryResource;

    public function __construct(
        \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory,
        \M2E\Temu\Model\ResourceModel\Category\Dictionary $categoryResource,
        \M2E\Temu\Model\ResourceModel\Product $productResource,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        int $templateCategoryId,
        array $data = []
    ) {
        $this->templateCategoryId = $templateCategoryId;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->productResource = $productResource;
        $this->categoryResource = $categoryResource;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId($this->getGridSelectorId());
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();
        $collection
            ->setListingProductModeOn()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');

        $collection->joinTable(
            ['lp' => $this->productResource->getMainTable()],
            'magento_product_id = entity_id',
            [
                'is_valid_category_attributes' => ProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
                'category_attributes_errors' => ProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS,
                'category_dictionary_id' => ProductResource::COLUMN_TEMPLATE_CATEGORY_ID,
                'status' => ProductResource::COLUMN_STATUS,
            ]
        );

        $collection->joinTable(
            ['category' => $this->categoryResource->getMainTable()],
            'id = category_dictionary_id',
            [
                'category_id' => CategoryDictionaryResource::COLUMN_CATEGORY_ID,
                'category_path' => CategoryDictionaryResource::COLUMN_PATH,
            ],
            null,
            'left'
        );

        $collection->addFieldToFilter('category_dictionary_id', $this->templateCategoryId);
        $collection->addFieldToFilter(
            'status',
            \M2E\Temu\Model\Product::STATUS_NOT_LISTED
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header' => __('Magento product ID'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer' => \M2E\Temu\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('category', [
            'header' => __(
                '%channel_title Categories',
                [
                    'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                ],
            ),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'frame_callback' => [$this, 'callbackColumnCategory'],
            'filter_condition_callback' => [$this, 'callbackFilterCategory'],
        ]);

        $this->addColumn('is_valid_category_attributes', [
            'header' => __('Product Data'),
            'sortable' => false,
            'align' => 'center',
            'index' => 'is_valid_category_attributes',
            'filter_index' => 'is_valid_category_attributes',
            'type' => 'options',
            'options' => [
                0 => __('Incomplete'),
                1 => __('Complete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ]);

        $this->addColumn('category_attributes_errors', [
            'header' => __('Error'),
            'width' => '200px',
            'index' => 'category_attributes_errors',
            'filter_index' => 'category_attributes_errors',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnErrors'],
            'filter_condition_callback' => [$this, 'callbackFilterColumnErrors'],
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport): string
    {
        if ($productTitle === '') {
            return (string)__('N/A');
        }

        $value = sprintf(
            '<span>%s</span>',
            $productTitle
        );

        $productSku = $row->getData('sku');
        if ($productSku === null) {
            $magentoProduct = $this->magentoProductFactory->create();
            $magentoProduct->setProductId((int)$row->getData('entity_id'));
            $productSku = $magentoProduct->getSku();
        }

        $value .= sprintf(
            '<br><strong>%s</strong>: %s',
            __('SKU'),
            $productSku
        );

        return $value;
    }

    protected function callbackFilterTitle($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
            ]
        );
    }

    public function callbackColumnCategory($value, $row, $column, $isExport): string
    {
        if ($row->getData('category_id') === null) {
            return 'Category is not set';
        }

        return sprintf(
            '%s (%s)',
            $row->getData('category_path'),
            $row->getData('category_id')
        );
    }

    protected function callbackFilterCategory($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        $fieldsToFilter = [
            ['attribute' => 'category_path', 'like' => '%' . $value . '%'],
        ];

        if (is_numeric($value)) {
            $fieldsToFilter[] = ['attribute' => 'category_id', 'eq' => $value];
        }

        $collection->addFieldToFilter($fieldsToFilter);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport): string
    {
        $status = $row->getData(ProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES);
        if ($status === null) {
            return '';
        }

        if (!$status) {
            return sprintf('<span style="color: red">%s</span>', __('Incomplete'));
        }

        return sprintf('<span style="color: green">%s</span>', __('Complete'));
    }

    protected function callbackFilterStatus($collection, $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null) {
            return;
        }

        $collection->addFieldToFilter(ProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES, ['eq' => $value]);
    }

    public function callbackColumnErrors($value, $row, $column, $isExport): string
    {
        $errorMessages = \M2E\Core\Helper\Json::decode($row->getData(ProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS) ?: '[]');

        if (!$errorMessages) {
            return '';
        }

        $errorList = [];
        foreach ($errorMessages as $message) {
            $errorList[] = sprintf('<li>%s</li>', ($message));
        }

        return sprintf(
            '<div class="product-type-validation-grid-error-message-block"><ul>%s</ul></div>',
            implode('', $errorList)
        );
    }

    public function callbackFilterColumnErrors($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value === null || $value === '') {
            return $this;
        }

        $collection->getSelect()->where(
            ProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS . ' LIKE ?',
            '%' . $value . '%'
        );

        return $this;
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getMainButtonsHtml()
    {
        $resetUrl = $this->getUrl(
            '*/category_attribute_validation/resetValidationData',
            [
                'template_category_id' => $this->templateCategoryId
            ]
        );

        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData([
            'id' => 'temu_category_validation',
            'label' => __('Validate Attributes'),
            'onclick' => "TemuCategoryAttributeValidatorObj.reValidate('{$resetUrl}');",
            'class' => 'action-primary'
        ]);

        return parent::getMainButtonsHtml() . $button->toHtml();
    }

    protected function _toHtml()
    {
        $progressBarHtml = sprintf('<div id="%s"></div>', $this->getProgressBarSelectorId());

        return $progressBarHtml . parent::_toHtml() . $this->getChildHtml('temu_validation');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setChild(
            'temu_validation',
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Template::class
            )->setTemplate('M2E_Temu::category/attributes/validation.phtml')
        );

        return $this;
    }

    public function getValidateUrl(): string
    {
        return $this->getUrl(
            '*/category_attribute_validation/validate',
            [
                'template_category_id' => $this->templateCategoryId
            ]
        );
    }

    public function getReloadGridUrl(): string
    {
        return $this->getUrl(
            '*/category_attribute_validation_modal/open',
            [
                'template_category_id' => $this->templateCategoryId
            ]
        );
    }

    public function getGridSelectorId(): string
    {
        return 'temu_category_attribute_validation_grid_modal';
    }

    public function getProgressBarSelectorId(): string
    {
        return 'temu_category_attribute_validation_progress_bar';
    }
}
