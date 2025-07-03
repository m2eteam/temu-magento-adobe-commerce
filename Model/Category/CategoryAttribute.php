<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category;

use M2E\Temu\Model\ResourceModel\Category\Attribute as AttributeResource;

class CategoryAttribute extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public const VALUE_MODE_NONE = 0;
    public const VALUE_MODE_RECOMMENDED = 1;
    public const VALUE_MODE_CUSTOM_VALUE = 2;
    public const VALUE_MODE_CUSTOM_ATTRIBUTE = 3;

    public const ATTRIBUTE_TYPE_BRAND = 'brand';
    public const ATTRIBUTE_TYPE_SALES = 'sales';
    public const ATTRIBUTE_TYPE_PRODUCT = 'product';
    public const ATTRIBUTE_TYPE_SIZE_CHART = 'size-chart';

    private const ATTRIBUTE_MULTI_VALUE_DELIMITER = '~';

    public function _construct()
    {
        parent::_construct();
        $this->_init(AttributeResource::class);
    }

    public function create(
        int $dictionaryId,
        string $attributeType,
        string $attributeId,
        string $attributeName,
        int $valueMode,
        array $recommendedValues,
        string $customValue,
        string $customAttributeValue
    ): self {
        $this->setCategoryDictionaryId($dictionaryId);
        $this->setAttributeType($attributeType);
        $this->setAttributeId($attributeId);
        $this->setAttributeName($attributeName);
        $this->setValueMode($valueMode);
        $this->setRecommendedValue($recommendedValues);
        $this->setCustomValue($customValue);
        $this->setCustomAttributeValue($customAttributeValue);

        return $this;
    }

    public function setCategoryDictionaryId(int $categoryDictionaryId): void
    {
        $this->setData(AttributeResource::COLUMN_CATEGORY_DICTIONARY_ID, $categoryDictionaryId);
    }

    public function getCategoryDictionaryId(): int
    {
        return (int)$this->getData(AttributeResource::COLUMN_CATEGORY_DICTIONARY_ID);
    }

    public function setAttributeType(string $type): void
    {
        $this->setData(AttributeResource::COLUMN_ATTRIBUTE_TYPE, $type);
    }

    public function getAttributeType(): string
    {
        return (string)$this->getData(AttributeResource::COLUMN_ATTRIBUTE_TYPE);
    }

    public function setAttributeId(string $id): void
    {
        $this->setData(AttributeResource::COLUMN_ATTRIBUTE_ID, $id);
    }

    public function getAttributeId(): string
    {
        return $this->getData(AttributeResource::COLUMN_ATTRIBUTE_ID);
    }

    public function setAttributeName(string $name): void
    {
        $this->setData(AttributeResource::COLUMN_ATTRIBUTE_NAME, $name);
    }

    public function getAttributeName(): string
    {
        return (string)$this->getData(AttributeResource::COLUMN_ATTRIBUTE_NAME);
    }

    public function setValueMode(int $mode): void
    {
        $this->setData(AttributeResource::COLUMN_VALUE_MODE, $mode);
    }

    public function getValueMode(): int
    {
        return (int)$this->getData(AttributeResource::COLUMN_VALUE_MODE);
    }

    public function setRecommendedValue(array $recommendedValue): void
    {
        $this->setData(
            AttributeResource::COLUMN_VALUE_RECOMMENDED,
            json_encode($recommendedValue, JSON_THROW_ON_ERROR)
        );
    }

    public function getRecommendedValue(): array
    {
        $recommendedValue = $this->getData(AttributeResource::COLUMN_VALUE_RECOMMENDED);
        if (empty($recommendedValue)) {
            return [];
        }

        return json_decode($recommendedValue, true);
    }

    public function setCustomValue(string $customValue): void
    {
        $this->setData(AttributeResource::COLUMN_VALUE_CUSTOM_VALUE, $customValue);
    }

    public function getCustomValue(): string
    {
        return (string)$this->getData(AttributeResource::COLUMN_VALUE_CUSTOM_VALUE);
    }

    public function setCustomAttributeValue(string $customAttribute): void
    {
        $this->setData(AttributeResource::COLUMN_VALUE_CUSTOM_ATTRIBUTE, $customAttribute);
    }

    public function getCustomAttributeValue(): string
    {
        return (string)$this->getData(AttributeResource::COLUMN_VALUE_CUSTOM_ATTRIBUTE);
    }

    // ----------------------------------------

    public function isValueModeNone(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_NONE;
    }

    public function isValueModeRecommended(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_RECOMMENDED;
    }

    public function isValueModeCustomAttribute(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM_ATTRIBUTE;
    }

    public function isValueModeCustomValue(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM_VALUE;
    }

    // ----------------------------------------

    public function isSalesAttribute(): bool
    {
        return $this->getAttributeType() === self::ATTRIBUTE_TYPE_SALES;
    }

    // ----------------------------------------

    public static function getCleanAttributeId(string $id): string
    {
        $parts = explode(self::ATTRIBUTE_MULTI_VALUE_DELIMITER, $id);

        return $parts[0] ?? $id;
    }

    public static function isAdditionalAttributeId(string $id): bool
    {
        return $id !== self::getCleanAttributeId($id);
    }
}
