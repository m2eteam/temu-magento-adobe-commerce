<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Listing\Wizard\Category;

class Same extends \M2E\Temu\Block\Adminhtml\Magento\AbstractContainer
{
    use \M2E\Temu\Block\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\Temu\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->setId('listingCategoryChooser');

        $url = $this->getUrl(
            '*/listing_wizard_category/completeStep',
            ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
        );

        $this->prepareButtons(
            [
                'label' => __('Continue'),
                'class' => 'action-primary forward',
                'onclick' => sprintf(
                    "TemuListingCategoryObj.modeSameSubmitData('%s')",
                    $this->getUrl(
                        '*/listing_wizard_category/assignModeSame',
                        ['id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId()],
                    ),
                ),
            ],
            $this->uiWizardRuntimeStorage->getManager(),
        );

        $this->_headerText = __('Categories');
    }

    protected function _toHtml()
    {
        $chooserBlock = $this
            ->getLayout()
            ->createBlock(
                \M2E\Temu\Block\Adminhtml\Category\CategoryChooser::class,
                '',
                ['categoryDictionary' => null],
            );

        return parent::_toHtml()
            . $chooserBlock->toHtml();
    }

    protected function _beforeToHtml()
    {
        $this->js->add(
            <<<JS
 require([
    'Temu/Listing/Wizard/Category'
], function() {
    window.TemuListingCategoryObj = new TemuListingCategory(null);
});
JS,
        );

        return parent::_beforeToHtml();
    }
}
