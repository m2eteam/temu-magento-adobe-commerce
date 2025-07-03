<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider\Attributes;

class Processor
{
    /** @var \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute[] */
    private array $attributes;
    private \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository;
    private \M2E\Temu\Helper\Module\Renderer\Description $descriptionRender;
    private \M2E\Temu\Model\Category\Attribute\Recommended\RetrieveValue $recommendedProcessor;
    private \M2E\Temu\Model\Product\DataProvider\Attributes\NotFoundAttributeDetector $notFoundAttributeDetector;

    public function __construct(
        \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository,
        \M2E\Temu\Helper\Module\Renderer\Description $descriptionRender,
        \M2E\Temu\Model\Category\Attribute\Recommended\RetrieveValue $recommendedProcessor,
        \M2E\Temu\Model\Product\DataProvider\Attributes\NotFoundAttributeDetector $notFoundAttributeDetector
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->descriptionRender = $descriptionRender;
        $this->recommendedProcessor = $recommendedProcessor;
        $this->notFoundAttributeDetector = $notFoundAttributeDetector;
    }

    /**
     * @param \M2E\Temu\Model\Product $listingProduct
     *
     * @return \M2E\Temu\Model\Product\DataProvider\Attributes\Item[]
     */
    public function getAttributes(
        \M2E\Temu\Model\Product $listingProduct
    ): array {
        $result = [];

        $this->initDictionaryProductAttributes($listingProduct);
        $attributes = $this->getDictionaryCategoryAttributes($listingProduct->getTemplateCategoryId());
        $magentoProduct = $listingProduct->getMagentoProduct();

        $this->notFoundAttributeDetector->clearMessages();
        $this->notFoundAttributeDetector->searchNotFoundAttributes($magentoProduct);

        foreach ($attributes as $attribute) {
            $this->processAttribute($attribute, $magentoProduct, $result);
        }

        $this->notFoundAttributeDetector->processNotFoundAttributes(
            $magentoProduct,
            $listingProduct->getListing()->getStoreId(),
            (string)__('Product')
        );

        return $result;
    }

    public function getWarningMessages(): array
    {
        return $this->notFoundAttributeDetector->getWarningMessages();
    }

    private function initDictionaryProductAttributes(\M2E\Temu\Model\Product $listingProduct): void
    {
        $dictionary = $listingProduct->getCategoryDictionary();
        foreach ($dictionary->getProductAttributes() as $attribute) {
            $this->attributes[$attribute->getId()] = $attribute;
        }
    }

    /**
     * @param int $categoryId
     *
     * @return \M2E\Temu\Model\Category\CategoryAttribute[]
     */
    private function getDictionaryCategoryAttributes(int $categoryId): array
    {
        return $this->attributeRepository->findByDictionaryId(
            $categoryId,
            [\M2E\Temu\Model\Category\CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT]
        );
    }

    private function processAttribute(
        \M2E\Temu\Model\Category\CategoryAttribute $attribute,
        \M2E\Temu\Model\Magento\Product $magentoProduct,
        array &$result
    ): void {
        $dictionaryAttribute = $this->getDictionaryAttributeById($this->getAttributeId($attribute));
        if ($attribute->isValueModeNone() || !$dictionaryAttribute) {
            return;
        }

        $recommendedValue = $this->recommendedProcessor->retrieveValue(
            $attribute,
            $dictionaryAttribute,
            $magentoProduct
        );

        if ($recommendedValue) {
            $this->handleRecommendedValue($recommendedValue, $dictionaryAttribute, $result);

            return;
        }

        switch ($attribute->getValueMode()) {
            case \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_RECOMMENDED:
                $this->handleRecommendedMode($attribute, $result);
                break;
            case \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_CUSTOM_VALUE:
                $this->handleCustomValueMode($attribute, $magentoProduct, $result);
                break;
            case \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_CUSTOM_ATTRIBUTE:
                $this->handleCustomAttributeMode($attribute, $magentoProduct, $result);
                break;
        }
    }

    private function getDictionaryAttributeById(
        string $attributeId
    ): ?\M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute {
        return $this->attributes[$attributeId] ?? null;
    }

    private function handleRecommendedValue(
        \M2E\Temu\Model\Category\Attribute\Recommended\Result $recommendedValue,
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $dictionaryAttribute,
        array &$result
    ): void {
        if ($recommendedValue->isFail()) {
            $this->notFoundAttributeDetector->addWarningMessage($recommendedValue->getFailMessages());
        } else {
            $result[] = $this->createAttributeItem(
                $dictionaryAttribute,
                null,
                $recommendedValue->getResult()
            );
        }
    }

    private function handleRecommendedMode(
        \M2E\Temu\Model\Category\CategoryAttribute $attribute,
        array &$result
    ): void {
        $dictionaryAttribute = $this->getDictionaryAttributeById($this->getAttributeId($attribute));
        foreach ($attribute->getRecommendedValue() as $valueId) {
            $result[] = $this->createAttributeItem(
                $dictionaryAttribute,
                null,
                (int)$valueId
            );
        }
    }

    private function handleCustomValueMode(
        \M2E\Temu\Model\Category\CategoryAttribute $attribute,
        \M2E\Temu\Model\Magento\Product $magentoProduct,
        array &$result
    ): void {
        $customValue = $attribute->getCustomValue();
        if (!empty($customValue)) {
            $dictionaryAttribute = $this->getDictionaryAttributeById($this->getAttributeId($attribute));
            $parsedValue = $this->descriptionRender->parseWithoutMagentoTemplate($customValue, $magentoProduct);
            $result[] = $this->createAttributeItem(
                $dictionaryAttribute,
                $parsedValue
            );
        }
    }

    private function handleCustomAttributeMode(
        \M2E\Temu\Model\Category\CategoryAttribute $attribute,
        \M2E\Temu\Model\Magento\Product $magentoProduct,
        array &$result
    ): void {
        $attributeValue = $magentoProduct->getAttributeValue($attribute->getCustomAttributeValue());
        if (!empty($attributeValue)) {
            $dictionaryAttribute = $this->getDictionaryAttributeById($this->getAttributeId($attribute));
            $result[] = $this->createAttributeItem(
                $dictionaryAttribute,
                $attributeValue
            );
        }
    }

    private function createAttributeItem(
        $dictionaryAttribute,
        ?string $customValue,
        ?int $recommendedValue = null
    ): \M2E\Temu\Model\Product\DataProvider\Attributes\Item {
        return new \M2E\Temu\Model\Product\DataProvider\Attributes\Item(
            $dictionaryAttribute->getPid(),
            $dictionaryAttribute->getRefPid(),
            $dictionaryAttribute->getTemplatePid(),
            $dictionaryAttribute->getName(),
            $customValue,
            $recommendedValue
        );
    }

    private function getAttributeId(\M2E\Temu\Model\Category\CategoryAttribute $attribute): string
    {
        $id = $attribute->getAttributeId();
        if (\M2E\Temu\Model\Category\CategoryAttribute::isAdditionalAttributeId($id)) {
            $id = \M2E\Temu\Model\Category\CategoryAttribute::getCleanAttributeId($id);
        }

        return $id;
    }
}
