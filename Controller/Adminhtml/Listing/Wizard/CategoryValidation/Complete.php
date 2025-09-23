<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Listing\Wizard\CategoryValidation;

use M2E\Temu\Model\Listing\Wizard\StepDeclarationCollectionFactory;

class Complete extends \M2E\Temu\Controller\Adminhtml\AbstractListing
{
    use \M2E\Temu\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory
    ) {
        parent::__construct();

        $this->wizardManagerFactory = $wizardManagerFactory;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();

        $manager = $this->wizardManagerFactory->createById($id);

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_CATEGORY_VALIDATION);

        return $this->redirectToIndex($id);
    }
}
