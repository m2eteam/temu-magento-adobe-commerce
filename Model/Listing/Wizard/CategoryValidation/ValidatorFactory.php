<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard\CategoryValidation;

use M2E\Temu\Model\Listing\Wizard\StepDeclarationCollectionFactory;

class ValidatorFactory
{
    private \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->wizardManagerFactory = $wizardManagerFactory;
    }

    public function create(\M2E\Temu\Model\Listing\Wizard $wizard): Validator
    {
        if ($wizard->getCurrentStepNick() !== StepDeclarationCollectionFactory::STEP_CATEGORY_VALIDATION) {
            throw new \LogicException('To proceed, please ensure the preceding steps are complete.');
        }

        $manager = $this->wizardManagerFactory->create($wizard);

        return $this->objectManager->create(Validator::class, [
            'wizardManager' => $manager,
        ]);
    }
}
