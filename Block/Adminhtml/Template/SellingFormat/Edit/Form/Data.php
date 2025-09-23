<?php

namespace M2E\Temu\Block\Adminhtml\Template\SellingFormat\Edit\Form;

use M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm;
use M2E\Temu\Model\Policy\SellingFormat;

class Data extends AbstractForm
{
    private \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper;
    private \M2E\Temu\Helper\Data\GlobalData $globalDataHelper;
    /** @var \M2E\Temu\Model\Policy\SellingFormat\BuilderFactory */
    private SellingFormat\BuilderFactory $templateSellingFormatBuilderFactory;

    public function __construct(
        \M2E\Temu\Model\Policy\SellingFormat\BuilderFactory $templateSellingFormatBuilderFactory,
        \M2E\Temu\Helper\Data\GlobalData $globalDataHelper,
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->templateSellingFormatBuilderFactory = $templateSellingFormatBuilderFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm(): Data
    {
        $attributes = $this->globalDataHelper->getValue('temu_attributes');

        $attributesByInputTypes = $this->getAttributesByInputTypes();

        $formData = $this->getFormData();
        $default = $this->getDefault();
        $formData = array_merge($default, $formData);

        $formData['fixed_price_modifier'] =
            \M2E\Core\Helper\Json::decode($formData['fixed_price_modifier']) ?: [];

        $form = $this->_formFactory->create();

        $form->addField(
            'selling_format_id',
            'hidden',
            [
                'name' => 'selling_format[id]',
                'value' => $formData['id'] ?? '',
            ]
        );

        $form->addField(
            'selling_format_title',
            'hidden',
            [
                'name' => 'selling_format[title]',
                'value' => $this->getTitle(),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_template_selling_format_edit_form_qty_and_duration',
            [
                'legend' => __('Quantity'),
                'collapsable' => true,
            ]
        );

        $preparedAttributes = [
            [
                'value' => SellingFormat::QTY_MODE_PRODUCT_FIXED,
                'label' => __('QTY'),
            ],
        ];

        if (
            $formData['qty_mode'] == SellingFormat::QTY_MODE_ATTRIBUTE
            && !$this->magentoAttributeHelper
                ->isExistInAttributesArray($formData['qty_custom_attribute'], $attributes)
            && $formData['qty_custom_attribute'] != ''
        ) {
            $preparedAttributes[] = [
                'attrs' => [
                    'attribute_code' => $formData['qty_custom_attribute'],
                    'selected' => 'selected',
                ],
                'value' => SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper->getAttributeLabel($formData['qty_custom_attribute']),
            ];
        }

        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['qty_mode'] == SellingFormat::QTY_MODE_ATTRIBUTE
                && $formData['qty_custom_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::QTY_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'container_id' => 'qty_mode_tr',
                'name' => 'selling_format[qty_mode]',
                'label' => __('Quantity'),
                'values' => [
                    SellingFormat::QTY_MODE_PRODUCT => __('Product Quantity'),
                    SellingFormat::QTY_MODE_NUMBER => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                            'new_option_value' => SellingFormat::QTY_MODE_ATTRIBUTE,
                        ],
                    ],
                ],
                'value' => $formData['qty_mode'] != SellingFormat::QTY_MODE_ATTRIBUTE
                    ? $formData['qty_mode'] : '',
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'The number of Items you want to sell on %channel_title.<br/><br/>' .
                    '<b>Product Quantity:</b> the number of Items on %channel_title will be the same as in Magento.<br/>' .
                    '<b>Custom Value:</b> set a Quantity in the Policy here.<br/>' .
                    '<b>Magento Attribute:</b> takes the number from the Attribute you specify.',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'qty_custom_attribute',
            'hidden',
            [
                'name' => 'selling_format[qty_custom_attribute]',
            ]
        );

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'container_id' => 'qty_mode_cv_tr',
                'label' => __('Quantity Value'),
                'name' => 'selling_format[qty_custom_value]',
                'value' => $formData['qty_custom_value'],
                'class' => 'validate-digits',
                'required' => true,
            ]
        );

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %',
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'container_id' => 'qty_percentage_tr',
                'label' => __('Quantity Percentage'),
                'name' => 'selling_format[qty_percentage]',
                'values' => $preparedAttributes,
                'value' => $formData['qty_percentage'],
                'tooltip' => __(
                    'Sets the percentage for calculation of Items number to be Listed ' .
                    'on %channel_title basing on Product Quantity or Magento Attribute. E.g., if Quantity Percentage ' .
                    'is set to 10% and Product Quantity is 100, the Quantity to be Listed on %channel_title will ' .
                    'be calculated as <br/>100 *10% = 10.<br/>',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                ),
            ]
        );

        $fieldset->addField(
            'qty_modification_mode',
            self::SELECT,
            [
                'container_id' => 'qty_modification_mode_tr',
                'label' => __('Conditional Quantity'),
                'name' => 'selling_format[qty_modification_mode]',
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => __('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => __('Enabled'),
                ],
                'value' => $formData['qty_modification_mode'],
                'tooltip' => __(
                    'Choose whether to limit the amount of Stock you list on %channel_title, eg ' .
                    'because you want to set some Stock aside for sales off %channel_title.<br/><br/>If this Setting ' .
                    'is <b>Enabled</b> you can specify the maximum Quantity to be Listed. If this Setting ' .
                    'is <b>Disabled</b> all Stock for the Product will be Listed as available on %channel_title.',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                ),
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'container_id' => 'qty_min_posted_value_tr',
                'label' => __('Minimum Quantity to Be Listed'),
                'name' => 'selling_format[qty_min_posted_value]',
                'value' => $formData['qty_min_posted_value'],
                'class' => 'Temu-validate-qty',
                'required' => true,
                'tooltip' => __(
                    'If you have 2 pieces in Stock but set a Minimum Quantity to Be Listed of 5, ' .
                    'Item will not be Listed on %channel_title.<br/> Otherwise, the Item will be Listed with ' .
                    'Quantity according to the Settings in the Selling Policy',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                ),
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'container_id' => 'qty_max_posted_value_tr',
                'label' => __('Maximum Quantity to Be Listed'),
                'name' => 'selling_format[qty_max_posted_value]',
                'value' => $formData['qty_max_posted_value'],
                'class' => 'Temu-validate-qty',
                'required' => true,
                'tooltip' => __(
                    'Set a maximum number to sell on %channel_title, e.g. if you ' .
                    'have 10 Items in Stock but want to keep 2 Items back, set a Maximum Quantity of 8.',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_template_selling_format_edit_form_prices',
            [
                'legend' => __('Price'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'price_table_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getPriceTableHtml(),
                'css_class' => 'Temu-fieldset-table',
            ]
        );

        // ----------------------------------------

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                !empty($formData['reference_link_attribute'])
                && $formData['reference_link_attribute'] === $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'reference_link_attribute',
            self::SELECT,
            [
                'name' => 'selling_format[reference_link_attribute]',
                'label' => __('Reference Link'),
                'values' => [
                    '' => __('None'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'Select Magento attribute with a link to the product from other marketplaces (e.g. Amazon, eBay, TikTok Shop, etc.)
                        This reference helps Temu to speed up price assessment. Links should begin with https:// or http://',
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        // ----------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_template_selling_format_edit_form_item_tax_code',
            [
                'legend' => __('Tax'),
                'collapsable' => true,
            ]
        );

        $this->addItemTaxCodeFields($fieldset, $attributesByInputTypes, $formData);

        // ----------------------------------------

        $this->setForm($form);

        $this->jsPhp->addConstants(
            [
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODE_PRODUCT' => SellingFormat::QTY_MODE_PRODUCT,
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODE_NUMBER' => SellingFormat::QTY_MODE_NUMBER,
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODE_ATTRIBUTE' => SellingFormat::QTY_MODE_ATTRIBUTE,
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODE_PRODUCT_FIXED' => SellingFormat::QTY_MODE_PRODUCT_FIXED,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODE_ATTRIBUTE' => SellingFormat::PRICE_MODE_ATTRIBUTE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODIFIER_ABSOLUTE_INCREASE' => SellingFormat::PRICE_MODIFIER_ABSOLUTE_INCREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODIFIER_ABSOLUTE_DECREASE' => SellingFormat::PRICE_MODIFIER_ABSOLUTE_DECREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODIFIER_PERCENTAGE_INCREASE' => SellingFormat::PRICE_MODIFIER_PERCENTAGE_INCREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODIFIER_PERCENTAGE_DECREASE' => SellingFormat::PRICE_MODIFIER_PERCENTAGE_DECREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_MODIFIER_ATTRIBUTE' => SellingFormat::PRICE_MODIFIER_ATTRIBUTE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE' => SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE' => SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE' => SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_ATTRIBUTE' => SellingFormat::PRICE_COEFFICIENT_ATTRIBUTE,
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODIFICATION_MODE_ON' => SellingFormat::QTY_MODIFICATION_MODE_ON,
                '\M2E\Temu\Model\Policy\SellingFormat::QTY_MODIFICATION_MODE_OFF' => SellingFormat::QTY_MODIFICATION_MODE_OFF,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE' => SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE,
                '\M2E\Temu\Model\Policy\SellingFormat::PRICE_COEFFICIENT_NONE' => SellingFormat::PRICE_COEFFICIENT_NONE,
            ]
        );

        $this->jsPhp->addConstants(
            [
                '\M2E\Temu\Model\Policy\Manager::TEMPLATE_DESCRIPTION' => \M2E\Temu\Model\Policy\Manager::TEMPLATE_DESCRIPTION,
                '\M2E\Temu\Model\Policy\Manager::MODE_PARENT' => \M2E\Temu\Model\Policy\Manager::MODE_PARENT,
                '\M2E\Temu\Model\Policy\Manager::MODE_CUSTOM' => \M2E\Temu\Model\Policy\Manager::MODE_CUSTOM,
                '\M2E\Temu\Model\Policy\Manager::MODE_TEMPLATE' => \M2E\Temu\Model\Policy\Manager::MODE_TEMPLATE,
                '\M2E\Temu\Model\Policy\Manager::TEMPLATE_SELLING_FORMAT' => \M2E\Temu\Model\Policy\Manager::TEMPLATE_SELLING_FORMAT,
                '\M2E\Temu\Model\Policy\Manager::TEMPLATE_SYNCHRONIZATION' => \M2E\Temu\Model\Policy\Manager::TEMPLATE_SYNCHRONIZATION,
            ]
        );

        $this->jsPhp->addConstants(
            [
                '\M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_NONE' => SellingFormat::ITEM_TAX_CODE_MODE_NONE,
                '\M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE' => SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE,
                '\M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE' => SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE,
            ]
        );

        $this->jsTranslator->addTranslations([
            'wrong_value_more_than_30' => __('Wrong value. Must be no more than 30. Max applicable ' .
                'length is 6 characters, including the decimal (e.g., 12.345).'),
            'Price Change is not valid.' => __('Price Change is not valid.'),
            'Wrong value. Only integer numbers.' => __('Wrong value. Only integer numbers.'),
            'Price' => __('Price'),
            '% of Price' => __('% of Price'),
        ]);
        $qtyMode = $formData['qty_mode'] ?? null;
        if (is_int($qtyMode)) {
            $jsMode = $qtyMode;
        } elseif (is_string($qtyMode)) {
            $jsMode = \M2E\Core\Helper\Data::escapeJs($qtyMode);
        }

        $qtyModificationMode = $formData['qty_modification_mode'] ?? null;
        if (is_int($qtyModificationMode)) {
            $jsQty = $qtyModificationMode;
        } elseif (is_string($qtyMode)) {
            $jsQty = \M2E\Core\Helper\Data::escapeJs($qtyModificationMode);
        }

        $this->js->add("Temu.formData.qty_mode = $jsMode;");
        $this->js->add("Temu.formData.qty_modification_mode = $jsQty;");

        $fixedPriceModifierRenderJs = '';
        if (!empty($formData['fixed_price_modifier'])) {
            $formDataJson = \M2E\Core\Helper\Json::encode($formData['fixed_price_modifier']);
            $fixedPriceModifierRenderJs = <<<JS
    TemuTemplateSellingFormatObj.renderFixedPriceChangeRows({$formDataJson});
JS;
        }

        $this->js->add(
            <<<JS
    require([
        'Temu/Template/SellingFormat',
    ], function(){
        window.TemuTemplateSellingFormatObj = new TemuTemplateSellingFormat();
        TemuTemplateSellingFormatObj.initObservers();

       {$fixedPriceModifierRenderJs}
    });
JS
        );

        return parent::_prepareForm();
    }

    private function getTitle()
    {
        $template = $this->globalDataHelper->getValue('temu_template_selling_format');

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    private function getFormData()
    {
        $template = $this->globalDataHelper->getValue('temu_template_selling_format');

        if ($template === null || $template->getId() === null) {
            return [];
        }

        return $template->getData();
    }

    private function getAttributesByInputTypes(): array
    {
        $attributes = $this->globalDataHelper->getValue('temu_attributes');

        return [
            'text' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text']),
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select']),
            'text_price' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'price']),
        ];
    }

    private function getDefault(): array
    {
        return $this->templateSellingFormatBuilderFactory->create()->getDefaultData();
    }

    private function getPriceTableHtml(): string
    {
        $block = $this->getLayout()->createBlock(
            \M2E\Temu\Block\Adminhtml\Template\SellingFormat\Edit\Form\PriceTable::class
        );
        $block->addData([
            'currency' => $this->getCurrency(),
            'form_data' => $this->getFormData(),
            'default' => $this->getDefault(),
            'attributes_by_input_types' => $this->getAttributesByInputTypes(),
        ]);

        return $block->toHtml();
    }

    private function addItemTaxCodeFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        array $attributesByInputTypes,
        array $formData
    ) {
        $mode = (int)$formData['item_tax_code_mode'];
        $attributeCode = $formData['item_tax_code_attribute'];
        $customValue = $formData['item_tax_code_custom_value'];

        $fieldset->addField(
            'item_tax_code_attribute',
            'hidden',
            [
                'name' => 'selling_format[item_tax_code_attribute]',
                'value' => $attributeCode,
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $mode === SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE
                && $attributeCode == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }

            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'item_tax_code_mode',
            self::SELECT,
            [
                'name' => 'selling_format[item_tax_code_mode]',
                'label' => __('Item Tax Code'),
                'values' => [
                    SellingFormat::ITEM_TAX_CODE_MODE_NONE => __('None'),
                    SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ],
                    ]
                ],
                'value' => $mode != SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE ? $mode : '',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'item_tax_code_custom_value',
            'text',
            [
                'container_id' => 'item_tax_code_custom_value_tr',
                'label' => 'Item Tax Code Value',
                'name' => 'selling_format[item_tax_code_custom_value]',
                'value' => $customValue,
                'required' => true,
            ]
        );
    }
}
