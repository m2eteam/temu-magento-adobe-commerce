<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Attribute;

class ValidateMagentoProduct
{
    private \M2E\Temu\Model\Category\Dictionary $categoryDictionary;
    private \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository;

    /** @var \M2E\Temu\Model\Category\CategoryAttribute[] */
    private array $attributesForValidate;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary $categoryDictionary,
        \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository
    ) {
        $this->categoryDictionary = $categoryDictionary;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param \M2E\Temu\Model\Magento\Product $magentoProduct
     *
     * @return string[]
     */
    public function validateProduct(\M2E\Temu\Model\Magento\Product $magentoProduct): array
    {
        $attributes = $this->findAttributesForValidation();
        if (empty($attributes)) {
            return [];
        }

        $errors = [];
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getCustomAttributeValue();
            $attributeValue = trim($magentoProduct->getAttributeValue($attributeCode));

            if (empty($attributeValue)) {
                $errors[] = (string)__(
                    'Attribute "%attribute_name" is missing value',
                    ['attribute_name' => $attribute->getAttributeName()]
                );
            }
        }

        return $errors;
    }

    /**
     * @return \M2E\Temu\Model\Category\CategoryAttribute[]
     */
    private function findAttributesForValidation(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->attributesForValidate)) {
            return $this->attributesForValidate;
        }

        $categoryAttributes = $this->attributeRepository->getAttributesWithCustomValue(
            $this->categoryDictionary->getId()
        );

        $categoryAttributesWithCustomValueById = [];
        foreach ($categoryAttributes as $attribute) {
            $categoryAttributesWithCustomValueById[$attribute->getAttributeId()] = $attribute;
        }

        foreach ($this->categoryDictionary->getProductAttributes() as $attribute) {
            if (!$attribute->isRequired()) {
                unset($categoryAttributesWithCustomValueById[$attribute->getId()]);
            }
        }

        return $this->attributesForValidate = array_values($categoryAttributesWithCustomValueById);
    }
}
