<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Edit;

use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form as AttributesForm;

class Form extends \M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm
{
    private const SAFETY_AND_COMPLIANCE_BRAND = [
        'pid' => 1467,
        'title' => 'Brand',
    ];

    private const SAFETY_AND_COMPLIANCE_OTHER = [
        [
            'id' => '1000001000',
            'title' => 'Country of Origin',
        ],
        [
            'id' => '1000001001',
            'title' => 'Province/Region (for China)',
        ],
    ];

    public function _construct()
    {
        parent::_construct();

        $this->setId('temuTemplateCategoryChooserSpecificEditForm');
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_specifics_form',
                'action' => '',
                'method' => 'post',
                'enctype' => 'multipart/form-data',
            ],
        ]);

        $formData = $this->getFormData();

        if (!empty($formData['sales_attributes'])) {
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
                $formData['sales_attributes']
            );
        }

        $realAttributes = $formData['real_attributes'] ?? [];
        $categoryAttributes = $this->getGeneralProductAttributes($realAttributes);
        $safetyAndComplianceAttributes = $this->getSafetyComplianceAttributes($realAttributes);

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

        $fieldset = $form->addFieldset(
            'dictionary',
            [
                'legend' => __('Category Attributes'),
                'collapsable' => false,
            ]
        );

        if (!empty($formData['real_attributes'])) {
            $this->addAttributesTable(
                $fieldset,
                'real_attributes',
                $categoryAttributes
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function getFormData()
    {
        return $this->getData('form_data');
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

    private function getGeneralProductAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute) {
            if (!$this->isSafetyComplianceAttribute($attribute)) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    private function getSafetyComplianceAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute) {
            if ($this->isSafetyComplianceAttribute($attribute)) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    private function isSafetyComplianceAttribute(array $attribute): bool
    {
        return $this->isBrandAttribute($attribute)
            || $this->isOtherSafetyComplianceAttribute($attribute);
    }

    private function isBrandAttribute(array $attribute): bool
    {
        return ($attribute['pid'] ?? null) === self::SAFETY_AND_COMPLIANCE_BRAND['pid']
            || ($attribute['title'] ?? '') === self::SAFETY_AND_COMPLIANCE_BRAND['title'];
    }

    private function isOtherSafetyComplianceAttribute(array $attribute): bool
    {
        foreach (self::SAFETY_AND_COMPLIANCE_OTHER as $item) {
            if (
                ($attribute['id'] ?? null) === $item['id']
                || ($attribute['title'] ?? '') === $item['title']
            ) {
                return true;
            }
        }

        return false;
    }
}
