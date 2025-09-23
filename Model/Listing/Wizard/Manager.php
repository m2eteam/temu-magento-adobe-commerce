<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard;

class Manager
{
    /** @var \M2E\Temu\Model\Listing\Wizard\Repository */
    private Repository $repository;
    private \M2E\Temu\Model\Listing\Wizard $wizard;
    /** @var \M2E\Temu\Model\Listing\Wizard\StepDeclarationCollection */
    private StepDeclarationCollection $stepCollection;
    private \M2E\Temu\Model\Listing\Wizard\ProductFactory $productFactory;
    /** @var \M2E\Temu\Model\Listing\Wizard\Step\BackHandlerFactory */
    private Step\BackHandlerFactory $stepBackHandlerFactory;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard $wizard,
        \M2E\Temu\Model\Listing\Wizard\StepDeclarationCollection $stepCollection,
        \M2E\Temu\Model\Listing\Wizard\ProductFactory $productFactory,
        \M2E\Temu\Model\Listing\Wizard\Step\BackHandlerFactory $stepBackHandlerFactory,
        Repository $repository
    ) {
        $this->repository = $repository;
        $this->wizard = $wizard;
        $this->stepCollection = $stepCollection;
        $this->productFactory = $productFactory;

        $this->stepCollection->setCurrent($this->wizard->getCurrentStepNick());
        $this->stepBackHandlerFactory = $stepBackHandlerFactory;
    }

    // ----------------------------------------

    public function getListing(): \M2E\Temu\Model\Listing
    {
        return $this->wizard->getListing();
    }

    // ----------------------------------------

    public function isCompleted(): bool
    {
        return $this->wizard->isCompleted();
    }

    // ----------------------------------------

    public function getWizardId(): int
    {
        return (int)$this->wizard->getId();
    }

    public function isWizardTypeGeneral(): bool
    {
        return $this->getWizardType() === \M2E\Temu\Model\Listing\Wizard::TYPE_GENERAL;
    }

    public function isWizardTypeUnmanaged(): bool
    {
        return $this->getWizardType() === \M2E\Temu\Model\Listing\Wizard::TYPE_UNMANAGED;
    }

    public function getWizardType(): string
    {
        return $this->wizard->getType();
    }

    // ----------------------------------------

    public function isCurrentStepIs(string $nick): bool
    {
        return $this->getCurrentStep()->getNick() === $nick;
    }

    public function getCurrentStep(): StepDeclaration
    {
        $nick = $this->wizard->getCurrentStepNick();

        return $this->stepCollection->getByNick($nick);
    }

    public function hasPreviousStep(): bool
    {
        return $this->getPreviousStep() !== null;
    }

    public function getPreviousStep(): ?StepDeclaration
    {
        if ($this->isCompleted()) {
            throw new \LogicException((string)__('You cannot proceed because the Wizard has already been completed.'));
        }

        return $this->stepCollection->findPreviousStep();
    }

    // ----------------------------------------

    public function setStep(string $stepNick): void
    {
        $step = $this->findStepEntity($stepNick);
        if ($step === null) {
            return;
        }

        $this->repository->save($this->wizard->setCurrentStepNick($stepNick));
        $this->stepCollection->setCurrent($stepNick);
    }

    public function backByStep(): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $currentStep = $this->getCurrentStep();
        $this->processBackHandler($currentStep);

        $step = $this->getStepEntity($currentStep->getNick());
        $step->notComplete();

        $this->repository->saveStep($step);

        do {
            $previousStep = $this->getPreviousStep();
            if ($previousStep === null) {
                break;
            }

            $previousEntity = $this->getStepEntity($previousStep->getNick());

            $isSkipped = $previousEntity->isSkipped();

            $previousEntity->notComplete();
            $this->repository->saveStep($previousEntity);

            $this->setStep($previousStep->getNick());

            if ($isSkipped) {
                $this->processBackHandler($previousStep);
            }
        } while ($isSkipped);
    }

    private function processBackHandler(StepDeclaration $step): void
    {
        if (!$step->hasBackHandler()) {
            return;
        }

        $backHandler = $this->stepBackHandlerFactory->create($step);
        $backHandler->process($this);
    }

    public function findNextStep(): ?StepDeclaration
    {
        if ($this->isCompleted()) {
            throw new \LogicException((string)__('You cannot proceed because the Wizard has already been completed.'));
        }

        return $this->stepCollection->findNextStep();
    }

    public function completeStep(string $stepNick, bool $isSkipped = false): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $step = $this->findStepEntity($stepNick);
        if ($step === null) {
            return;
        }

        if ($this->wizard->getCurrentStepNick() !== $step->getNick()) {
            throw new \LogicException('To proceed, please ensure the preceding steps are complete.');
        }

        if (!$step->isCompleted()) {
            if ($isSkipped) {
                $step->skip();
            } else {
                $step->complete();
            }

            $this->repository->saveStep($step);
        }

        $nextStepDefinition = $this->findNextStep();
        if ($nextStepDefinition === null) {
            $this->complete();

            return;
        }

        $this->wizard->setCurrentStepNick($nextStepDefinition->getNick());
        $this->repository->save($this->wizard);
    }

    // ----------------------------------------

    public function setStepData(string $stepNick, array $data): void
    {
        $step = $this->findStepEntity($stepNick);
        if ($step === null) {
            return;
        }

        $step->setResultData($data);
        $this->repository->saveStep($step);
    }

    public function getStepData(string $stepNick): array
    {
        $step = $this->findStepEntity($stepNick);
        if ($step === null) {
            throw new \LogicException(sprintf("Listing Wizard step '%s' not found", $stepNick));
        }

        return $step->getResultData();
    }

    // ----------------------------------------

    private function getStepEntity(string $stepNick): Step
    {
        $entity = $this->findStepEntity($stepNick);
        if ($entity === null) {
            throw new \LogicException(sprintf("Listing Wizard step '%s' not found", $stepNick));
        }

        return $entity;
    }

    private function findStepEntity(string $nick): ?Step
    {
        foreach ($this->wizard->getSteps() as $step) {
            if ($step->getNick() === $nick) {
                return $step;
            }
        }

        return null;
    }

    // ----------------------------------------

    /**
     * @param int[] $magentoProductsIds
     *
     * @return void
     */
    public function addProducts(array $magentoProductsIds): void
    {
        if (empty($magentoProductsIds)) {
            return;
        }

        $products = [];
        foreach ($magentoProductsIds as $magentoProductId) {
            $products[] = $this->productFactory->create()
                                               ->init($this->wizard, (int)$magentoProductId);
        }

        $this->repository->addOrUpdateProducts($products);
    }

    /**
     * @param \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct
     *
     * @return void
     */
    public function addUnmanagedProduct(\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct): ?\M2E\Temu\Model\Listing\Wizard\Product
    {
        if ($this->findProductByMagentoId($unmanagedProduct->getMagentoProductId())) {
            return null;
        }

        $product = $this->productFactory->create()
                                        ->init($this->wizard, $unmanagedProduct->getMagentoProductId())
                                        ->setUnmanagedProductId($unmanagedProduct->getId());

        $this->repository->saveProduct($product);

        return $product;
    }

    /**
     * @param int $magentoProductId
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product|null
     */
    public function findProductByMagentoId(int $magentoProductId): ?\M2E\Temu\Model\Listing\Wizard\Product
    {
        return $this->repository->findProductByMagentoId($magentoProductId, $this->wizard);
    }

    /**
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function getProducts(): array
    {
        return $this->repository->findAllProducts($this->wizard);
    }

    /**
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function getNotProcessedProducts(): array
    {
        return $this->repository->findNotProcessed($this->wizard);
    }

    public function setProductsCategoryIdSame(int $categoryDictionaryId): void
    {
        $this->repository->setCategoryDictionaryIdForAllProducts($this->wizard, $categoryDictionaryId);
    }

    /**
     * @param int $id
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product|null
     */
    public function findProductById(int $id): ?\M2E\Temu\Model\Listing\Wizard\Product
    {
        return $this->repository->findProductById($id, $this->wizard);
    }

    /**
     * @param int[] $wizardProductsIds
     *
     * @return void
     */
    public function markProductsAsProcessed(array $wizardProductsIds): void
    {
        $this->repository->markProductsAsCompleted($this->wizard, $wizardProductsIds);
    }

    // ----------------------------------------

    public function cancel(): void
    {
        $this->complete();
    }

    public function complete(): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $this->wizard->complete($this->repository->getProcessedProductsCount($this->wizard));

        $this->repository->save($this->wizard);

        $this->clearProducts();
    }

    // ----------------------------------------

    public function clearProducts(): void
    {
        $this->repository->removeAllProducts($this->wizard);
    }

    /**
     * @param int[] $productsIds
     *
     * @return void
     */
    public function resetCategoryIdByProductId(array $productsIds): void
    {
        $this->repository->resetCategoryIdByProductId($this->wizard, $productsIds);
    }

    public function setProductCountTotal(int $count): void
    {
        $this->wizard->setProductCountTotal($count);
        $this->repository->save($this->wizard);
    }

    /**
     * @param int $productLimit
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function findProductsForValidateCategoryAttributes(int $productLimit): array
    {
        return $this->repository->findProductsForValidateCategoryAttributes($this->wizard, $productLimit);
    }

    public function resetCategoryValidationData(): void
    {
        $this->repository->resetCategoryAttributesValidationData($this->wizard);
    }

    public function getProductsCount(): int
    {
        return $this->repository->getProductCount($this->wizard);
    }
}
