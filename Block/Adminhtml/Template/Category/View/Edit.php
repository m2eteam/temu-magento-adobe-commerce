<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\View;

class Edit extends \M2E\Temu\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private \M2E\Temu\Model\Category\Dictionary $dictionary;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary $dictionary,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dictionary = $dictionary;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->removeButton('save');

        $this->setId('temuConfigurationCategoryViewTabsItemSpecificsEdit');
        $this->_controller = 'adminhtml_template_category_view';

        $this->_headerText = '';

        $this->updateButton(
            'reset',
            'onclick',
            'TemuTemplateCategorySpecificsObj.resetSpecifics()'
        );

        $editUrl = $this->_urlBuilder->getUrl(
            '*/category/saveCategoryAttributes',
            ['back' => 'edit']
        );

        $closeUrl = $this->_urlBuilder->getUrl(
            '*/category/SaveCategoryAttributes',
            ['back' => 'categories_grid']
        );

        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => __('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'class_name' => \M2E\Temu\Block\Adminhtml\Magento\Button\SplitButton::class,
            'onclick' => "TemuTemplateCategorySpecificsObj.saveAndEditClick('$editUrl')",
            'options' => [
                'save' => [
                    'label' => __('Save And Back'),
                    'onclick' => "TemuTemplateCategorySpecificsObj.saveAndCloseClick('$closeUrl')",
                ],
            ],
        ];

        $this->addButton('save_buttons', $saveButtons);

        if (!$this->dictionary->hasRecordsOfAttributes()) {
            $this->removeButton('reset');
            $this->removeButton('save_and_continue');
        }
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/template_category/index');
    }

    public function getDictionary(): \M2E\Temu\Model\Category\Dictionary
    {
        return $this->dictionary;
    }
}
