<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Attribute;

use M2E\Temu\Model\Template\Category;

class Manager
{
    private const MIN_SALES_ATTRIBUTES_COUNT = 1;
    private const MAX_SALES_ATTRIBUTES_COUNT = 2;

    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private \M2E\Temu\Model\Category\Attribute\Repository $categoryAttributeRepository;
    private \Magento\Framework\App\ResourceConnection $resource;
    private \M2E\Temu\Model\Template\Category\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \M2E\Temu\Model\Template\Category\DiffFactory $diffFactory;
    private \M2E\Temu\Model\Template\Category\ChangeProcessorFactory $changeProcessorFactory;
    private \M2E\Temu\Model\Template\Category\AffectedListingsProductsFactory $affectedListingsProductsFactory;
    private \M2E\Temu\Model\AttributeMapping\GeneralService $attributeMappingGeneralService;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Model\Category\Attribute\Repository $categoryAttributeRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \M2E\Temu\Model\Template\Category\SnapshotBuilderFactory $snapshotBuilderFactory,
        \M2E\Temu\Model\Template\Category\DiffFactory $diffFactory,
        \M2E\Temu\Model\Template\Category\ChangeProcessorFactory $changeProcessorFactory,
        \M2E\Temu\Model\Template\Category\AffectedListingsProductsFactory $affectedListingsProductsFactory,
        \M2E\Temu\Model\AttributeMapping\GeneralService $attributeMappingGeneralService
    ) {
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->categoryAttributeRepository = $categoryAttributeRepository;
        $this->resource = $resource;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->diffFactory = $diffFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->affectedListingsProductsFactory = $affectedListingsProductsFactory;
        $this->attributeMappingGeneralService = $attributeMappingGeneralService;
    }

    /**
     * @param \M2E\Temu\Model\Category\CategoryAttribute[] $attributes
     * @param \M2E\Temu\Model\Category\Dictionary $dictionary
     *
     * @return void
     * @throws \Exception|\Throwable
     */
    public function createOrUpdateAttributes(
        array $attributes,
        \M2E\Temu\Model\Category\Dictionary $dictionary
    ): void {
        $attributesSortedById = [];
        $countOfUsedAttributes = 0;

        foreach ($attributes as $attribute) {
            $attributesSortedById[$attribute->getAttributeId()] = $attribute;
            if (!$this->isEmptyValues($attribute)) {
                $countOfUsedAttributes++;
            }
        }

        $transaction = $this->resource->getConnection()->beginTransaction();
        try {
            $oldSnapshot = $this->getSnapshot($dictionary);

            $existedAttributes = $this->categoryAttributeRepository
                ->findByDictionaryId($dictionary->getId());

            foreach ($existedAttributes as $existedAttribute) {
                $inputAttribute = $attributesSortedById[$existedAttribute->getAttributeId()] ?? null;
                if ($inputAttribute === null) {
                    continue;
                }

                $existedAttributeId = $inputAttribute->getAttributeId();
                if ($this->isEmptyAdditionalAttribute($inputAttribute)) {
                    $this->categoryAttributeRepository->delete($existedAttribute);
                    unset($attributesSortedById[$existedAttributeId]);
                } else {
                    $this->updateAttribute($existedAttribute, $inputAttribute);
                    unset($attributesSortedById[$existedAttribute->getAttributeId()]);
                }
            }

            foreach ($attributesSortedById as $attribute) {
                if ($this->isEmptyAdditionalAttribute($attribute)) {
                    continue;
                }
                $this->createAttribute($attribute);
            }

            $newSnapshot = $this->getSnapshot($dictionary);

            $this->addInstruction($dictionary, $oldSnapshot, $newSnapshot);

            $dictionary->setUsedProductAttributes($countOfUsedAttributes);
            $dictionary->installStateSaved();
            $this->categoryDictionaryRepository->save($dictionary);

            $this->attributeMappingGeneralService->create($dictionary->getRelatedAttributes());
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        $transaction->commit();
    }

    public function areRequiredSalesAttributesCountSelected(array $salesAttributes): bool
    {
        $selectedAttributes = 0;
        foreach ($salesAttributes as $salesAttribute) {
            if ($this->isAttributeSelected($salesAttribute)) {
                $selectedAttributes++;
            }
        }

        return (
            $selectedAttributes >= self::MIN_SALES_ATTRIBUTES_COUNT
            && $selectedAttributes <= self::MAX_SALES_ATTRIBUTES_COUNT
        );
    }

    public function getSalesAttributeCountError(): string
    {
        return (string)__(
            'Invalid variant attributes: You must select at least %1 and no more than %2 variant attributes.',
            self::MIN_SALES_ATTRIBUTES_COUNT,
            self::MAX_SALES_ATTRIBUTES_COUNT
        );
    }

    private function isAttributeSelected(array $salesAttribute): bool
    {
        $valueMode = (int)$salesAttribute['value_mode'];

        return ($valueMode == Category::VALUE_MODE_CUSTOM_ATTRIBUTE
                && !empty($salesAttribute['value_custom_attribute']))
            || ($valueMode == Category::VALUE_MODE_CUSTOM_VALUE
                && !empty($salesAttribute['value_custom_value']))
            || ($valueMode == Category::VALUE_MODE_TEMU_RECOMMENDED
                && !empty($salesAttribute['value_temu_recommended']));
    }

    private function updateAttribute(
        \M2E\Temu\Model\Category\CategoryAttribute $existedAttribute,
        \M2E\Temu\Model\Category\CategoryAttribute $inputAttribute
    ) {
        $existedAttribute->setCategoryDictionaryId($inputAttribute->getCategoryDictionaryId());
        $existedAttribute->setAttributeType($inputAttribute->getAttributeType());
        $existedAttribute->setAttributeId($inputAttribute->getAttributeId());
        $existedAttribute->setAttributeName($inputAttribute->getAttributeName());
        $existedAttribute->setValueMode($inputAttribute->getValueMode());
        $existedAttribute->setRecommendedValue($inputAttribute->getRecommendedValue());
        $existedAttribute->setCustomValue($inputAttribute->getCustomValue());
        $existedAttribute->setCustomAttributeValue($inputAttribute->getCustomAttributeValue());

        $this->categoryAttributeRepository->save($existedAttribute);
    }

    private function createAttribute(\M2E\Temu\Model\Category\CategoryAttribute $attribute)
    {
        $this->categoryAttributeRepository->create($attribute);
    }

    private function getSnapshot(\M2E\Temu\Model\Category\Dictionary $dictionary): array
    {
        return $this->snapshotBuilderFactory
            ->create()
            ->setModel($dictionary)
            ->getSnapshot();
    }

    private function addInstruction(
        \M2E\Temu\Model\Category\Dictionary $dictionary,
        array $oldSnapshot,
        array $newSnapshot
    ): void {
        $diff = $this->diffFactory->create();
        $diff->setOldSnapshot($oldSnapshot);
        $diff->setNewSnapshot($newSnapshot);

        $affectedListingsProducts = $this->affectedListingsProductsFactory->create();
        $affectedListingsProducts->setModel($dictionary);

        $changeProcessor = $this->changeProcessorFactory->create();
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );
    }

    private function isEmptyAdditionalAttribute(\M2E\Temu\Model\Category\CategoryAttribute $attribute): bool
    {
        return \M2E\Temu\Model\Category\CategoryAttribute::isAdditionalAttributeId($attribute->getAttributeId())
            && $this->isEmptyValues($attribute);
    }

    private function isEmptyValues(\M2E\Temu\Model\Category\CategoryAttribute $attribute): bool
    {
        return empty($attribute->getCustomValue())
            && empty($attribute->getCustomAttributeValue())
            && empty($attribute->getRecommendedValue());
    }
}
