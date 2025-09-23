<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Template\Category;

use M2E\Temu\Model\Category\CategoryAttribute;

class DictionaryMapper
{
    private const SAFETY_AND_COMPLIANCE_BRAND = [
        'pid' => 1467,
        'title' => 'Brand',
    ];

    private const SAFETY_AND_COMPLIANCE_OTHER = [
        [
            'id' => '1000001000',
            'title' => 'Country of Origin',
        ],
        [
            'id' => '1000001001',
            'title' => 'Province/Region (for China)',
        ],
    ];

    private \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository;
    private \M2E\Temu\Model\AttributeMapping\GeneralService $generalService;
    /** @var \M2E\Core\Model\AttributeMapping\Pair[] */
    private array $generalAttributeMapping;

    public function __construct(
        \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository,
        \M2E\Temu\Model\AttributeMapping\GeneralService $generalService
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->generalService = $generalService;
    }

    /**
     * @see \M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element\Dictionary
     */
    public function getProductAttributes(\M2E\Temu\Model\Category\Dictionary $dictionary): array
    {
        $generalMappingAttributes = $this->getGeneralAttributesMappingByAttributeId();
        $savedAttributes = $this->loadSavedAttributes($dictionary, [
            CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT,
        ]);

        $attributes = [];
        foreach ($dictionary->getProductAttributes() as $productAttribute) {
            if ($this->isSafetyComplianceAttribute($productAttribute)) {
                continue;
            }

            $item = $this->map($productAttribute, $savedAttributes, $generalMappingAttributes);
            $attributeVariants = $this->getProductAttributeVariants($productAttribute, $savedAttributes);

            if ($item['required']) {
                array_unshift($attributes, $item, ...$attributeVariants);
                continue;
            }

            array_unshift($attributeVariants, $item);
            $attributes = array_merge($attributes, $attributeVariants);
        }

        $attributes = $this->sortAttributesByTitle($attributes);

        return $this->sortAttributesByRelations($attributes);
    }

    /**
     * @see \M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element\Dictionary
     */
    public function getSalesAttributes(\M2E\Temu\Model\Category\Dictionary $dictionary): array
    {
        $savedAttributes = $this->loadSavedAttributes($dictionary, [
            CategoryAttribute::ATTRIBUTE_TYPE_SALES,
        ]);

        $attributes = [];
        foreach ($dictionary->getSalesAttributes() as $productAttribute) {
            $item = $this->map($productAttribute, $savedAttributes);

            if ($item['required']) {
                array_unshift($attributes, $item);
                continue;
            }

            $attributes[] = $item;
        }

        return $this->sortAttributesByTitle($attributes);
    }

    public function getSafetyAndComplianceAttributes(\M2E\Temu\Model\Category\Dictionary $dictionary): array
    {
        $generalMappingAttributes = $this->getGeneralAttributesMappingByAttributeId();
        $savedAttributes = $this->loadSavedAttributes($dictionary, [
            CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT,
        ]);

        $attributes = [];
        foreach ($dictionary->getProductAttributes() as $productAttribute) {
            if (!$this->isSafetyComplianceAttribute($productAttribute)) {
                continue;
            }

            $item = $this->map($productAttribute, $savedAttributes, $generalMappingAttributes);
            $attributeVariants = $this->getProductAttributeVariants($productAttribute, $savedAttributes);

            if ($item['required']) {
                array_unshift($attributes, $item, ...$attributeVariants);
                continue;
            }

            array_unshift($attributeVariants, $item);
            $attributes = array_merge($attributes, $attributeVariants);
        }

        $attributes = $this->sortAttributesByTitle($attributes);

        return $this->sortAttributesByRelations($attributes);
    }

    /**
     * @param \M2E\Temu\Model\Category\Dictionary\AbstractAttribute $attribute
     * @param \M2E\Temu\Model\Category\CategoryAttribute[] $savedAttributes
     * @param \M2E\Core\Model\AttributeMapping\Pair[] $generalMappingAttributes
     *
     * @return array
     */
    private function map(
        \M2E\Temu\Model\Category\Dictionary\AbstractAttribute $attribute,
        array $savedAttributes,
        array $generalMappingAttributes = []
    ): array {
        $item = [
            'id' => $attribute->getId(),
            'title' => $attribute->getName(),
            'attribute_type' => $attribute->getType(),
            'type' => $attribute->isMultipleSelected() ? 'select_multiple' : 'select',
            'required' => $attribute->isRequired(),
            'is_customized' => $attribute->isCustomised(),
            'min_values' => $attribute->isRequired() ? 1 : 0,
            'max_values' => $attribute->isMultipleSelected() ? count($attribute->getValues()) : 1,
            'values' => [],
            'template_attribute' => [],
            'pid' => $attribute->getPid(),
            'parent_template_pid' => $attribute->getParentTemplatePid(),
            'multiple_selected_max_count' => $attribute->getMultipleSelectedMaxCount()
        ];

        $existsAttribute = $savedAttributes[$attribute->getId()] ?? null;
        $generalMapping = $generalMappingAttributes[$attribute->getName()] ?? null;
        if (
            $existsAttribute !== null
            || $generalMapping !== null
        ) {
            $item['template_attribute'] = [
                'id' => $existsAttribute ? $existsAttribute->getAttributeId() : null,
                'template_category_id' => $existsAttribute ? $existsAttribute->getId() : null,
                'mode' => '1',
                'attribute_title' => $existsAttribute ? $existsAttribute->getAttributeId() : $attribute->getName(),
                'value_mode' => $existsAttribute !== null
                    ? $existsAttribute->getValueMode()
                    : ($generalMapping !== null ?
                        \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_CUSTOM_ATTRIBUTE :
                        \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_NONE),
                'value_temu_recommended' => $existsAttribute ? $existsAttribute->getRecommendedValue() : null,
                'value_custom_value' => $existsAttribute ? $existsAttribute->getCustomValue() : null,
                'value_custom_attribute' => $existsAttribute !== null
                    ? $existsAttribute->getCustomAttributeValue()
                    : ($generalMapping !== null ? $generalMapping->getMagentoAttributeCode() : null),
            ];
        }

        foreach ($attribute->getValues() as $value) {
            $relations = [];
            foreach ($value->getValueRelation() as $relation) {
                $relations[] = [
                    'child_template_pid' => $relation->getChildTemplatePid(),
                    'values_ids' => $relation->getValueIds(),
                ];
            }
            $item['values'][] = [
                'id' => $value->getId(),
                'value' => $value->getName(),
                'children_relation' => $relations,
            ];
        }

        return $item;
    }

    private function loadSavedAttributes(
        \M2E\Temu\Model\Category\Dictionary $dictionary,
        array $typeFilter = []
    ): array {
        $attributes = [];

        $savedAttributes = $this
            ->attributeRepository
            ->findByDictionaryId($dictionary->getId(), $typeFilter);

        foreach ($savedAttributes as $attribute) {
            $attributes[$attribute->getAttributeId()] = $attribute;
        }

        return $attributes;
    }

    public function sortAttributesByTitle(array $attributes): array
    {
        usort($attributes, function ($prev, $next) {
            return strcmp($prev['title'], $next['title']);
        });

        $requiredAttributes = [];
        foreach ($attributes as $index => $attribute) {
            if (isset($attribute['required']) && $attribute['required'] === true) {
                $requiredAttributes[] = $attribute;
                unset($attributes[$index]);
            }
        }

        return array_merge($requiredAttributes, $attributes);
    }

    /**
     * @return \M2E\Core\Model\AttributeMapping\Pair[]
     */
    private function getGeneralAttributesMappingByAttributeId(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->generalAttributeMapping)) {
            return $this->generalAttributeMapping;
        }

        $result = [];
        foreach ($this->generalService->getAll() as $item) {
            $result[$item->getChannelAttributeCode()] = $item;
        }

        return $this->generalAttributeMapping = $result;
    }

    private function sortAttributesByRelations(array $attributes): array
    {
        $attributesMap = array_column($attributes, null, 'id');
        $sorted = [];
        $processed = [];
        foreach ($attributes as $attribute) {
            if (!isset($processed[$attribute['id']]) && $attribute['parent_template_pid'] === null) {
                $this->processAttributeWithChildren($attribute, $attributesMap, $sorted, $processed);
            }
        }

        return $sorted;
    }

    private function processAttributeWithChildren(
        array $attribute,
        array $attributesMap,
        array &$sorted,
        array &$processed
    ): void {
        $attributeId = (string)$attribute['id'];
        if (isset($processed[$attributeId])) {
            return;
        }

        $sorted[] = $attribute;
        $processed[$attributeId] = true;

        foreach ($attributesMap as $childAttribute) {
            if ($childAttribute['parent_template_pid'] === (int)$attribute['id']) {
                $this->processAttributeWithChildren($childAttribute, $attributesMap, $sorted, $processed);
            }
        }
    }

    private function getProductAttributeVariants(
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute,
        array $savedAttributes
    ): array {
        $variants = [];

        foreach ($savedAttributes as $savedAttribute) {
            if ($this->isMatchingAttribute($attribute, $savedAttribute)) {
                $modifiedAttribute = clone $attribute;
                $modifiedAttribute->setId($savedAttribute->getAttributeId());
                $variants[] = $this->map(
                    $modifiedAttribute,
                    [$savedAttribute->getAttributeId() => $savedAttribute]
                );
            }
        }

        return $variants;
    }

    private function isMatchingAttribute(
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute,
        \M2E\Temu\Model\Category\CategoryAttribute $savedAttribute
    ): bool {
        $savedId = $savedAttribute->getAttributeId();
        $cleanedId = \M2E\Temu\Model\Category\CategoryAttribute::getCleanAttributeId($savedId);

        return $savedId !== $attribute->getId()
            && $cleanedId === $attribute->getId();
    }

    private function isSafetyComplianceAttribute(\M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute): bool
    {
        return $this->isBrandAttribute($attribute)
            || $this->isOtherSafetyComplianceAttribute($attribute);
    }

    private function isBrandAttribute(\M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute): bool
    {
        return $attribute->getPid() === self::SAFETY_AND_COMPLIANCE_BRAND['pid']
            || $attribute->getName() === self::SAFETY_AND_COMPLIANCE_BRAND['title'];
    }

    private function isOtherSafetyComplianceAttribute(\M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute): bool
    {
        foreach (self::SAFETY_AND_COMPLIANCE_OTHER as $item) {
            if (
                (string)$attribute->getRefPid() === $item['id']
                || $attribute->getName() === $item['title']
            ) {
                return true;
            }
        }

        return false;
    }
}
