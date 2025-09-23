<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\View\Edit;

use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form as AttributesForm;

class Form extends \M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \M2E\Temu\Block\Adminhtml\Template\Category\DictionaryMapper $dictionaryMapper;

    public function __construct(
        \M2E\Temu\Block\Adminhtml\Template\Category\DictionaryMapper $dictionaryMapper,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->dictionaryMapper = $dictionaryMapper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/saveCategoryAttributes'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        /** @var \M2E\Temu\Block\Adminhtml\Template\Category\View\Edit $parentBlock */
        $parentBlock = $this->getParentBlock();
        $dictionary = $parentBlock->getDictionary();

        $form->addField(
            'dictionary_id',
            'hidden',
            [
                'name' => 'dictionary_id',
                'value' => $dictionary->getId(),
            ]
        );

        $salesAttributes = $this->dictionaryMapper->getSalesAttributes($dictionary);
        if (!empty($salesAttributes)) {
            $fieldset = $form->addFieldset(
                'sales_attributes_fieldset',
                [
                    'legend' => __('Variation Attributes'),
                    'collapsable' => false,
                    'tooltip' => '<p>' . __(
                        'Variation attributes correspond to Temu Sales attributes. Most categories require at'
                            . ' least one such attribute to define product variations, such as size, color, or '
                            . 'material. Utilize these fields to provide additional details about your products, '
                            . 'helping buyers refine their searches and make informed purchasing decisions.'
                    ) . '</p>',
                ]
            );

            $this->addAttributesTable(
                $fieldset,
                'sales_attributes',
                $salesAttributes
            );
        }

        $safetyAndComplianceAttributes = $this->dictionaryMapper->getSafetyAndComplianceAttributes($dictionary);
        if (!empty($safetyAndComplianceAttributes)) {
            $fieldset = $form->addFieldset(
                'safety_compliance_attributes_fieldset',
                [
                    'legend' => __('Safety and Compliance'),
                    'collapsable' => false,
                ]
            );

            $this->addAttributesTable(
                $fieldset,
                'safety_compliance_attributes',
                $safetyAndComplianceAttributes
            );
        }

        $realAttributes = $this->dictionaryMapper->getProductAttributes($dictionary);
        if ($realAttributes !== []) {
            $fieldset = $form->addFieldset(
                'attributes',
                [
                    'legend' => __('Product Attributes'),
                    'collapsable' => false,
                ]
            );

            $this->addAttributesTable($fieldset, 'real_attributes', $realAttributes);
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->jsPhp->addConstants(
            [
                '\M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED' =>
                    \M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED,
                '\M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_VALUE' =>
                    \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_VALUE,
                '\M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_ATTRIBUTE' =>
                    \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_ATTRIBUTE,
                '\M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE' =>
                    \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE,
            ]
        );

        $this->js->addRequireJs(
            [
                'etcs' => 'Temu/Template/Category/Specifics',
            ],
            <<<JS
        window.TemuTemplateCategorySpecificsObj = new TemuTemplateCategorySpecifics();
JS
        );

        return parent::_prepareLayout();
    }

    private function addAttributesTable(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        string $id,
        array $attributes
    ): void {
        /** @var AttributesForm\Renderer\Dictionary $renderer */
        $renderer = $this->getLayout()->createBlock(AttributesForm\Renderer\Dictionary::class);

        $config = [
            'specifics' => $attributes,
            'attribute_type' => $id,
        ];

        $field = $fieldset->addField($id, AttributesForm\Element\Dictionary::class, $config);
        $field->setRenderer($renderer);
    }
}
