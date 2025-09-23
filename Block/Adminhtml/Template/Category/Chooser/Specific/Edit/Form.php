<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Edit;

use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form as AttributesForm;

class Form extends \M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm
{
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

        $salesAttributes = $formData['sales_attributes'] ?? [];
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

        $safetyAndComplianceAttributes = $formData['safety_and_compliance'] ?? [];
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

        $realAttributes = $formData['real_attributes'] ?? [];
        if (!empty($realAttributes)) {
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
                    $realAttributes
                );
            }
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
}
