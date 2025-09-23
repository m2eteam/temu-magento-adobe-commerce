<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Instruction\SynchronizationTemplate\Checker;

class NotListedChecker extends \M2E\Temu\Model\Instruction\SynchronizationTemplate\Checker\AbstractChecker
{
    private \M2E\Temu\Model\ScheduledAction\CreateService $scheduledActionCreate;
    private \M2E\Temu\Model\ScheduledAction\Repository $scheduledActionRepository;
    private \M2E\Temu\Model\Product\ActionCalculator $actionCalculator;

    public function __construct(
        \M2E\Temu\Model\ScheduledAction\CreateService $scheduledActionCreate,
        \M2E\Temu\Model\ScheduledAction\Repository $scheduledActionRepository,
        \M2E\Temu\Model\Instruction\SynchronizationTemplate\Checker\Input $input,
        \M2E\Temu\Model\Product\ActionCalculator $actionCalculator
    ) {
        parent::__construct($input);
        $this->scheduledActionCreate = $scheduledActionCreate;
        $this->scheduledActionRepository = $scheduledActionRepository;
        $this->actionCalculator = $actionCalculator;
    }

    public function isAllowed(): bool
    {
        if (!parent::isAllowed()) {
            return false;
        }

        $listingProduct = $this->getInput()->getListingProduct();

        if (!$listingProduct->isListable() || !$listingProduct->isStatusNotListed()) {
            return false;
        }

        if ($listingProduct->isInvalidCategoryAttributes()) {
            return false;
        }

        return true;
    }

    public function process(): void
    {
        $product = $this->getInput()->getListingProduct();

        $calculateResult = $this->actionCalculator->calculateToList($product);
        if (!$calculateResult->isActionList()) {
            $this->tryRemoveExistScheduledAction();

            return;
        }

        if (
            $this->getInput()->getScheduledAction() !== null
            && $this->getInput()->getScheduledAction()->isActionTypeList()
        ) {
            return;
        }

        $this->scheduledActionCreate->create(
            $this->getInput()->getListingProduct(),
            \M2E\Temu\Model\Product::ACTION_LIST,
            \M2E\Temu\Model\Product::STATUS_CHANGER_SYNCH,
            [],
            $calculateResult->getConfigurator()->getAllowedDataTypes(),
            false,
            $calculateResult->getConfigurator(),
            $calculateResult->getVariantSettings()
        );
    }

    private function tryRemoveExistScheduledAction(): void
    {
        if ($this->getInput()->getScheduledAction() === null) {
            return;
        }

        if ($this->getInput()->getScheduledAction()->isForce()) {
            return;
        }

        $this->scheduledActionRepository->remove($this->getInput()->getScheduledAction());
    }
}
