<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Category\Edit;

use M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm;

class TitleForm extends AbstractForm
{
    private \M2E\Temu\Model\Category\Dictionary $categoryDictionary;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary $categoryDictionary,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->categoryDictionary = $categoryDictionary;
    }

    protected function _prepareForm()
    {

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_category_title_form',
                ],
            ]
        );

        $form->addField(
            'dictionary_id',
            'hidden',
            [
                'name' => 'dictionary_id',
                'value' => $this->categoryDictionary->getId()
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_category_title_fieldset',
            []
        );

        $fieldset->addField(
            'category_title',
            'text',
            [
                'name' => 'title',
                'class' => 'validate-no-empty Temu-listing-title',
                'label' => __('Title'),
                'value' => $this->categoryDictionary->getTitle(),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
