<?php

namespace M2E\Temu\Model\Category;

use M2E\Temu\Model\Category\CategoryAttribute;
use M2E\Temu\Model\Category\Dictionary\AbstractAttribute as DictionaryAbstractAttribute;
use M2E\Temu\Model\ResourceModel\Category\Dictionary as DictionaryResource;

//TODO
class Dictionary extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public const DRAFT_STATE = 1;
    public const SAVED_STATE = 2;

    private \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository;
    private \M2E\Temu\Model\Category\Dictionary\Attribute\Serializer $attributeSerializer;
    private \M2E\Temu\Model\ResourceModel\Product\CollectionFactory $listingProductCollectionFactory;
    private \M2E\Temu\Model\Category\Dictionary\AttributeClassifier $attributeClassifier;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\AttributeClassifier $attributeClassifier,
        \M2E\Temu\Model\ResourceModel\Product\CollectionFactory $listingProductCollectionFactory,
        \M2E\Temu\Model\Category\Attribute\Repository $attributeRepository,
        \M2E\Temu\Model\Category\Dictionary\Attribute\Serializer $attributeSerializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->attributeRepository = $attributeRepository;
        $this->attributeSerializer = $attributeSerializer;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->attributeClassifier = $attributeClassifier;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(DictionaryResource::class);
    }

    public function create(
        string $region,
        int $categoryId,
        string $title,
        string $path,
        array $salesAttributes,
        array $productAttributes,
        array $categoryRules,
        array $authorizedBrands,
        int $totalProductAttributes,
        bool $hasRequiredProductAttributes,
        int $totalSalesAttributes,
        bool $hasRequiredSalesAttributes
    ): Dictionary {
        $this->setState(self::DRAFT_STATE);

        $this->setRegion($region);
        $this->setCategoryId($categoryId);
        $this->setTitle($title);
        $this->setPath($path);
        $this->setSalesAttributes($salesAttributes);
        $this->setProductAttributes($productAttributes);
        $this->setCategoryRules($categoryRules);
        $this->setAuthorizedBrands($authorizedBrands);
        $this->setUsedSalesAttributes(0);
        $this->setTotalProductAttributes($totalProductAttributes);
        $this->setTotalSalesAttributes($totalSalesAttributes);
        $this->setHasRequiredProductAttributes($hasRequiredProductAttributes);
        $this->setHasRequiredSalesAttributes($hasRequiredSalesAttributes);

        return $this;
    }

    /**
     * @return CategoryAttribute[]
     */
    public function getRelatedAttributes(): array
    {
        return $this->attributeRepository->findByDictionaryId($this->getId());
    }

    public function hasRecordsOfAttributes(): bool
    {
        return $this->attributeRepository->getCountByDictionaryId($this->getId()) > 0;
    }

    public function isAllRequiredAttributesFilled(): bool
    {
        $allAttributes = $this->getProductAttributes();

        $requiredAttributeIds = array_map(
            fn(DictionaryAbstractAttribute $attribute) => $attribute->getId(),
            array_filter(
                $allAttributes,
                fn(DictionaryAbstractAttribute $attribute) => $attribute->isRequired()
                    && !$attribute->getParentTemplatePid()
            )
        );

        $filledAttributeIds = array_map(
            fn(CategoryAttribute $attribute) => $attribute->getAttributeId(),
            array_filter(
                $this->getRelatedAttributes(),
                fn(CategoryAttribute $attribute) => !$attribute->isValueModeNone()
            )
        );

        return count(array_diff($requiredAttributeIds, $filledAttributeIds)) === 0;
    }

    public function setRegion(string $region): void
    {
        $this->setData(DictionaryResource::COLUMN_REGION, $region);
    }

    public function getRegion(): string
    {
        return (string)$this->getData(DictionaryResource::COLUMN_REGION);
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->setData(DictionaryResource::COLUMN_CATEGORY_ID, $categoryId);
    }

    public function getCategoryId(): string
    {
        return (string)$this->getData(DictionaryResource::COLUMN_CATEGORY_ID);
    }

    public function setTitle(string $title): self
    {
        $this->setData(DictionaryResource::COLUMN_TITLE, $title);

        return $this;
    }

    public function getTitle(): string
    {
        return (string)$this->getData(DictionaryResource::COLUMN_TITLE);
    }

    public function setState(int $state): void
    {
        $this->setData(DictionaryResource::COLUMN_STATE, $state);
    }

    public function getState(): int
    {
        return (int)$this->getData(DictionaryResource::COLUMN_STATE);
    }

    public function setPath(string $path): void
    {
        $this->setData(DictionaryResource::COLUMN_PATH, $path);
    }

    public function getPath(): string
    {
        return $this->getData(DictionaryResource::COLUMN_PATH);
    }

    /**
     * @param \M2E\Temu\Model\Category\Dictionary\Attribute\SalesAttribute[] $salesAttributes //TODO: add
     */
    public function setSalesAttributes(array $salesAttributes): void
    {
        $this->setData(
            DictionaryResource::COLUMN_SALES_ATTRIBUTES,
            $this->attributeSerializer->serializeSalesAttributes($salesAttributes)
        );
    }

    /**
     * @return \M2E\Temu\Model\Category\Dictionary\Attribute\SalesAttribute[]
     */
    public function getSalesAttributes(): array
    {
        $attributes = $this->getData(DictionaryResource::COLUMN_SALES_ATTRIBUTES);
        if (empty($attributes)) {
            return [];
        }

        return $this->attributeSerializer->unSerializeSalesAttributes($attributes);
    }

    /**
     * @param \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute[] $productAttributes
     */
    public function setProductAttributes(array $productAttributes)
    {
        $this->setData(
            DictionaryResource::COLUMN_PRODUCT_ATTRIBUTES,
            $this->attributeSerializer->serializeProductAttributes($productAttributes)
        );
    }

    /**
     * @return \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute[]
     */
    public function getProductAttributes(): array
    {
        $attributes = $this->attributeSerializer->unSerializeProductAttributes(
            $this->getData(DictionaryResource::COLUMN_PRODUCT_ATTRIBUTES)
        );

        foreach ($attributes as $attribute) {
            if ($this->attributeClassifier->isBrandAttribute($attribute)) {
                $attribute->setIsCustomized(true);
            }
        }

        return $attributes;
    }

    public function setCategoryRules(array $categoryRules): void
    {
        $this->setData(
            DictionaryResource::COLUMN_CATEGORY_RULES,
            json_encode($categoryRules, JSON_THROW_ON_ERROR)
        );
    }

    public function getCategoryRules(): array
    {
        $rules = $this->getData(DictionaryResource::COLUMN_CATEGORY_RULES);
        if ($rules === null) {
            return [];
        }

        return (array)json_decode($rules, true);
    }

    public function setAuthorizedBrands(array $authorizedBrands): void
    {
        $this->setData(
            DictionaryResource::COLUMN_AUTHORIZED_BRANDS,
            json_encode($authorizedBrands, JSON_THROW_ON_ERROR)
        );
    }

    public function getAuthorizedBrands(): array
    {
        $brands = $this->getData(DictionaryResource::COLUMN_AUTHORIZED_BRANDS);
        if ($brands === null) {
            return [];
        }

        return (array)json_decode($brands, true);
    }

    public function getTotalSalesAttributes(): int
    {
        return (int)$this->getData(DictionaryResource::COLUMN_TOTAL_SALES_ATTRIBUTES);
    }

    public function setTotalSalesAttributes(int $totalSalesAttributes): void
    {
        $this->setData(DictionaryResource::COLUMN_TOTAL_SALES_ATTRIBUTES, $totalSalesAttributes);
    }

    public function getTotalProductAttributes(): int
    {
        return (int)$this->getData(DictionaryResource::COLUMN_TOTAL_PRODUCT_ATTRIBUTES);
    }

    public function setTotalProductAttributes(int $totalProductAttributes): void
    {
        $this->setData(DictionaryResource::COLUMN_TOTAL_PRODUCT_ATTRIBUTES, $totalProductAttributes);
    }

    public function setUsedSalesAttributes(int $usedSalesAttributes): void
    {
        $this->setData(DictionaryResource::COLUMN_USED_SALES_ATTRIBUTES, $usedSalesAttributes);
    }

    public function getUsedSalesAttributes(): int
    {
        return (int)$this->getData(DictionaryResource::COLUMN_USED_SALES_ATTRIBUTES);
    }

    public function setUsedProductAttributes(int $count): void
    {
        $this->setData(DictionaryResource::COLUMN_USED_PRODUCT_ATTRIBUTES, $count);
    }

    public function getUsedProductAttributes(): int
    {
        return (int)$this->getData(DictionaryResource::COLUMN_USED_PRODUCT_ATTRIBUTES);
    }

    public function hasRequiredProductAttributes(): bool
    {
        return (bool)$this->getData(DictionaryResource::COLUMN_HAS_REQUIRED_PRODUCT_ATTRIBUTES);
    }

    public function setHasRequiredProductAttributes(bool $hasRequiredProductAttributes): void
    {
        $this->setData(DictionaryResource::COLUMN_HAS_REQUIRED_PRODUCT_ATTRIBUTES, $hasRequiredProductAttributes);
    }

    public function getHasRequiredSalesAttributes(): bool
    {
        return (bool)$this->getData(DictionaryResource::COLUMN_HAS_REQUIRED_SALES_ATTRIBUTES);
    }

    public function setHasRequiredSalesAttributes(bool $hasRequiredSalesAttributes): void
    {
        $this->setData(DictionaryResource::COLUMN_HAS_REQUIRED_SALES_ATTRIBUTES, $hasRequiredSalesAttributes);
    }

    public function markCategoryAsValid(): self
    {
        return $this->setData(DictionaryResource::COLUMN_IS_VALID, 1);
    }

    public function markCategoryAsInvalid(): self
    {
        return $this->setData(DictionaryResource::COLUMN_IS_VALID, 0);
    }

    public function isCategoryValid(): bool
    {
        return (bool)$this->getData(DictionaryResource::COLUMN_IS_VALID);
    }

    public function setCreateDate(\DateTime $dateTime)
    {
        $this->setData(
            DictionaryResource::COLUMN_CREATE_DATE,
            $dateTime->format('Y-m-d H:i:s')
        );
    }

    public function getCreateDate(): \DateTime
    {
        return \M2E\Core\Helper\Date::createDateGmt(
            $this->getData(DictionaryResource::COLUMN_CREATE_DATE)
        );
    }

    public function setUpdateDate(\DateTime $dateTime): void
    {
        $this->setData(
            DictionaryResource::COLUMN_UPDATE_DATE,
            $dateTime->format('Y-m-d H:i:s')
        );
    }

    public function getUpdateDate(): \DateTime
    {
        return \M2E\Core\Helper\Date::createDateGmt(
            $this->getData(DictionaryResource::COLUMN_UPDATE_DATE)
        );
    }

    // ----------------------------------------

    public function isStateSaved(): bool
    {
        return $this->getData(DictionaryResource::COLUMN_STATE) === self::SAVED_STATE;
    }

    public function installStateSaved(): void
    {
        $this->setData(DictionaryResource::COLUMN_STATE, self::SAVED_STATE);
    }

    public function getPathWithCategoryId(): string
    {
        return sprintf('%s (%s)', $this->getPath(), $this->getCategoryId());
    }

    public function isLocked(): bool
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->getSelect()->where('template_category_id = ?', $this->getId());

        return (bool)$collection->getSize();
    }

    public function delete(): void
    {
        foreach ($this->getRelatedAttributes() as $attribute) {
            $attribute->delete();
        }

        parent::delete();
    }

    public function getTrackedAttributes(): array
    {
        $trackedAttributes = [];
        foreach ($this->getRelatedAttributes() as $attribute) {
            if (!$attribute->isValueModeCustomAttribute()) {
                continue;
            }

            $trackedAttributes[] = $attribute->getCustomAttributeValue();
        }

        return array_unique(array_filter($trackedAttributes));
    }
}
