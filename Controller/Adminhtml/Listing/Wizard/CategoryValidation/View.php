<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Listing\Wizard\CategoryValidation;

use M2E\Temu\Model\Listing\Wizard\StepDeclarationCollectionFactory;

class View extends \M2E\Temu\Controller\Adminhtml\Listing\Wizard\StepAbstract
{
    use \M2E\Temu\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_CATEGORY_VALIDATION;
    }

    protected function process(\M2E\Temu\Model\Listing $listing)
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                         ->createBlock(
                             \M2E\Temu\Block\Adminhtml\Listing\Wizard\CategoryValidation\Grid::class
                         );
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->getResultPage()
             ->getConfig()
             ->getTitle()->prepend(__('Validate Category Specifics'));

        $this->addContent(
            $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\Listing\Wizard\CategoryValidation\View::class),
        );

        return $this->getResult();
    }
}
