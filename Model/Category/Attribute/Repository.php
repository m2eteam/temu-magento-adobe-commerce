<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Attribute;

use M2E\Temu\Model\Category\CategoryAttribute;
use M2E\Temu\Model\ResourceModel\Category\Attribute as AttributeResource;

class Repository
{
    private \M2E\Temu\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeCollectionFactory;
    private AttributeResource $attributeResource;

    public function __construct(
        \M2E\Temu\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeCollectionFactory,
        AttributeResource $attributeResource
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->attributeResource = $attributeResource;
    }

    public function create(CategoryAttribute $entity): void
    {
        $this->attributeResource->save($entity);
    }

    public function save(CategoryAttribute $attrEntity): void
    {
        $this->attributeResource->save($attrEntity);
    }

    public function delete(CategoryAttribute $attrEntity): void
    {
        $this->attributeResource->delete($attrEntity);
    }

    /**
     * @return CategoryAttribute[]
     */
    public function findByDictionaryId(
        int $dictionaryId,
        array $typeFilter = []
    ): array {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter(
            AttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
            ['eq' => $dictionaryId]
        );

        if ($typeFilter !== []) {
            $collection->addFieldToFilter(
                AttributeResource::COLUMN_ATTRIBUTE_TYPE,
                ['in' => $typeFilter]
            );
        }

        return array_values($collection->getItems());
    }

    public function getCountByDictionaryId(int $dictionaryId): int
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter(
            AttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
            $dictionaryId
        );

        return $collection->getSize();
    }

    /**
     * @return string[]
     */
    public function getAllCustomAttributesNames(): array
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter(
            AttributeResource::COLUMN_VALUE_MODE,
            \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_CUSTOM_ATTRIBUTE
        );

        $collection->removeAllFieldsFromSelect();

        $collection->addFieldToSelect(AttributeResource::COLUMN_ATTRIBUTE_NAME);
        $collection->distinct(true);

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[] = $item->getAttributeName();
        }

        return $result;
    }

    /**
     * @param int $dictionaryId
     *
     * @return CategoryAttribute[]
     */
    public function getAttributesWithCustomValue(int $dictionaryId): array
    {
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter(
            AttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
            ['eq' => $dictionaryId]
        );

        $collection->addFieldToFilter(
            AttributeResource::COLUMN_VALUE_MODE,
            \M2E\Temu\Model\Category\CategoryAttribute::VALUE_MODE_CUSTOM_ATTRIBUTE
        );

        $collection->addFieldToFilter(
            AttributeResource::COLUMN_ATTRIBUTE_TYPE,
            ['in' => [CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT, CategoryAttribute::ATTRIBUTE_TYPE_SALES]]
        );

        return array_values($collection->getItems());
    }
}
