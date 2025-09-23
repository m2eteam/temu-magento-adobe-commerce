<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard;

use M2E\Temu\Model\ResourceModel\Listing\Wizard\Product as WizardProductResource;

class Product extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    private \M2E\Temu\Model\Listing\Wizard $wizard;
    private \M2E\Temu\Model\Magento\Product\Cache $magentoProductModel;

    /** @var \M2E\Temu\Model\Listing\Wizard\Repository */
    private Repository $repository;
    private \M2E\Temu\Model\Category\Dictionary\Repository $dictionaryRepository;
    private \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory;

    public function __construct(
        Repository $repository,
        \M2E\Temu\Model\Category\Dictionary\Repository $dictionaryRepository,
        \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->repository = $repository;
        $this->dictionaryRepository = $dictionaryRepository;
        $this->magentoProductFactory = $magentoProductFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(WizardProductResource::class);
    }

    public function init(\M2E\Temu\Model\Listing\Wizard $wizard, int $magentoProductId): self
    {
        $this
            ->setData(WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId())
            ->setData(WizardProductResource::COLUMN_MAGENTO_PRODUCT_ID, $magentoProductId);

        return $this;
    }

    public function initWizard(\M2E\Temu\Model\Listing\Wizard $wizard): self
    {
        $this->wizard = $wizard;

        return $this;
    }

    public function getWizard(): \M2E\Temu\Model\Listing\Wizard
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->wizard)) {
            $this->wizard = $this->repository->get($this->getWizardId());
        }

        return $this->wizard;
    }

    public function getWizardId(): int
    {
        return (int)$this->getData(WizardProductResource::COLUMN_WIZARD_ID);
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(WizardProductResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function getMagentoProduct(): \M2E\Temu\Model\Magento\Product\Cache
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoProductModel)) {
            $this->magentoProductModel = $this->magentoProductFactory->create();
            $this->magentoProductModel->setProductId($this->getMagentoProductId());
            $this->magentoProductModel->setStoreId($this->getWizard()->getListing()->getStoreId());
        }

        return $this->magentoProductModel;
    }

    public function getUnmanagedProductId(): ?int
    {
        $value = $this->getData(WizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID);

        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function setUnmanagedProductId(int $value): self
    {
        $this->setData(WizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID, $value);

        return $this;
    }

    public function setCategoryId(int $value): self
    {
        $this->setData(WizardProductResource::COLUMN_CATEGORY_ID, $value);

        return $this;
    }

    public function getCategoryDictionaryId(): ?int
    {
        $value = $this->getData(WizardProductResource::COLUMN_CATEGORY_ID);
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function getCategoryDictionary(): ?\M2E\Temu\Model\Category\Dictionary
    {
        $dictionaryId = $this->getCategoryDictionaryId();
        if ($dictionaryId === null) {
            return null;
        }

        return $this->dictionaryRepository->get($dictionaryId);
    }

    public function getCategoryId(): ?string
    {
        $dictionary = $this->getCategoryDictionary();
        if ($dictionary === null) {
            return null;
        }

        return $dictionary->getCategoryId();
    }

    public function isProcessed(): bool
    {
        return (bool)$this->getData(WizardProductResource::COLUMN_IS_PROCESSED);
    }

    public function processed(): self
    {
        $this->setData(WizardProductResource::COLUMN_IS_PROCESSED, 1);

        return $this;
    }

    public function markCategoryAttributesAsValid(): void
    {
        $this->setCategoryAttributesValid(true);
        $this->setCategoryAttributesErrors([]);
    }

    /**
     * @param string[] $errors
     *
     * @return void
     */
    public function markCategoryAttributesAsInvalid(array $errors): void
    {
        $this->setCategoryAttributesValid(false);
        $this->setCategoryAttributesErrors($errors);
    }

    public function isInvalidCategoryAttributes(): bool
    {
        $value = $this->getData(WizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES);

        return $value === null ? false : !$value;
    }

    private function setCategoryAttributesValid(bool $isValid): void
    {
        $this->setData(WizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES, $isValid);
    }

    private function setCategoryAttributesErrors(array $errors): void
    {
        $value = null;
        if (!empty($errors)) {
            $value = json_encode($errors);
        }

        $this->setData(WizardProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS, $value);
    }

    /**
     * @return string[]
     */
    public function getCategoryAttributesErrors(): array
    {
        $value = $this->getData(WizardProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }
}
