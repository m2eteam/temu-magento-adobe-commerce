<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Product\Grid\AllItems;

use M2E\Temu\Model\ResourceModel\Account as AccountResource;
use M2E\Temu\Model\ResourceModel\Listing as ListingResource;
use M2E\Temu\Model\ResourceModel\Product as ProductResource;
use M2E\Temu\Model\ResourceModel\Tag\ListingProduct\Relation as TagProductRelationResource;
use M2E\Temu\Model\ResourceModel\Tag as TagResource;
use Magento\Framework\Api\Search\SearchResultInterface;

class Collection extends \Magento\Framework\Data\Collection implements SearchResultInterface
{
    use \M2E\Temu\Model\ResourceModel\SearchResultTrait;

    public const PRIMARY_COLUMN = 'product_id';
    public const FILTER_BY_ERROR_CODE_FILED_NAME = 'error_code'; // see ui xml

    private bool $isAlreadyFilteredByErrorCode = false;

    /** @var \M2E\Temu\Model\ResourceModel\Product */
    private ProductResource $listingProductResource;
    /** @var \M2E\Temu\Model\ResourceModel\Listing */
    private ListingResource $listingResource;
    /** @var \M2E\Temu\Model\ResourceModel\Account */
    private AccountResource $accountResource;
    private \M2E\Core\Model\ResourceModel\Magento\Product\Collection $wrappedCollection;
    private \M2E\Temu\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage;
    private \M2E\Temu\Model\ResourceModel\Tag\ListingProduct\Relation $tagProductRelationResource;
    /** @var \M2E\Temu\Model\ResourceModel\Tag */
    private TagResource $tagResource;
    private bool $isGetAllItemsFromFilter = false;

    public function __construct(
        ProductResource $listingProductResource,
        ListingResource $listingResource,
        AccountResource $accountResource,
        TagProductRelationResource $tagProductRelationResource,
        TagResource $tagResource,
        \M2E\Temu\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage,
        \M2E\Core\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
    ) {
        parent::__construct($entityFactory);
        $this->listingProductResource = $listingProductResource;
        $this->listingResource = $listingResource;
        $this->accountResource = $accountResource;
        $this->productUiRuntimeStorage = $productUiRuntimeStorage;
        $this->wrappedCollection = $magentoProductCollectionFactory->create();
        $this->tagProductRelationResource = $tagProductRelationResource;
        $this->tagResource = $tagResource;
        $this->prepareCollection();
    }

    private function prepareCollection(): void
    {
        $this->wrappedCollection->setItemObjectClass(ProductResource\Grid\AllItems\Entity::class);

        $this->wrappedCollection->setListingProductModeOn();

        $this->wrappedCollection->getSelect()->distinct();

        $this->wrappedCollection->addAttributeToSelect('sku');
        $this->wrappedCollection->addAttributeToSelect('name');

        $this->wrappedCollection->joinTable(
            ['lp' => $this->listingProductResource->getMainTable()],
            sprintf('%s = entity_id', ProductResource::COLUMN_MAGENTO_PRODUCT_ID),
            [
                self::PRIMARY_COLUMN => ProductResource::COLUMN_ID,
                'product_' . ProductResource::COLUMN_STATUS => ProductResource::COLUMN_STATUS,
                'product_' . ProductResource::COLUMN_LISTING_ID => ProductResource::COLUMN_LISTING_ID,
                'product_' . ProductResource::COLUMN_ONLINE_QTY => ProductResource::COLUMN_ONLINE_QTY,
                'product_' . ProductResource::COLUMN_ONLINE_MIN_PRICE => ProductResource::COLUMN_ONLINE_MIN_PRICE,
                'product_' . ProductResource::COLUMN_ONLINE_MAX_PRICE => ProductResource::COLUMN_ONLINE_MAX_PRICE,
                'product_' . ProductResource::COLUMN_CHANNEL_PRODUCT_ID => ProductResource::COLUMN_CHANNEL_PRODUCT_ID,
                'product_' . ProductResource::COLUMN_TEMPLATE_CATEGORY_ID => ProductResource::COLUMN_TEMPLATE_CATEGORY_ID,
            ],
        );

        $this->wrappedCollection->joinTable(
            ['listing' => $this->listingResource->getMainTable()],
            sprintf('%s = product_%s', ListingResource::COLUMN_ID, ProductResource::COLUMN_LISTING_ID),
            [
                'listing_' . ListingResource::COLUMN_STORE_ID => ListingResource::COLUMN_STORE_ID,
                'listing_' . ListingResource::COLUMN_ACCOUNT_ID => ListingResource::COLUMN_ACCOUNT_ID,
                'listing_' . ListingResource::COLUMN_TITLE => ListingResource::COLUMN_TITLE,
                'listing_' . ListingResource::COLUMN_SITE_ID => ListingResource::COLUMN_SITE_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID => ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID => ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID => ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
            ],
        );

        $this->wrappedCollection->joinTable(
            ['account' => $this->accountResource->getMainTable()],
            sprintf('%s = listing_%s', AccountResource::COLUMN_ID, ListingResource::COLUMN_ACCOUNT_ID),
            [
                'account_' . AccountResource::COLUMN_TITLE => AccountResource::COLUMN_TITLE,
            ],
        );

        $this->wrappedCollection->getSelect()->distinct();
    }

    public function getItems()
    {
        $items = $this->wrappedCollection->getItems();
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = (int)$item['product_id'];
        }

        if (!$this->isGetAllItemsFromFilter) {
            $this->productUiRuntimeStorage->loadByIds(array_unique($productIds));
        }

        return $items;
    }

    public function getProducts(): array
    {
        return $this->productUiRuntimeStorage->getAll();
    }

    public function getSelect()
    {
        return $this->wrappedCollection->getSelect();
    }

    // ----------------------------------------

    public function addFieldToFilter($field, $condition)
    {
        if ($field === 'product_online_price') {
            $this->buildFilterByPrice($condition);

            return $this;
        }

        if ($field === self::FILTER_BY_ERROR_CODE_FILED_NAME) {
            $this->addFilterByTag($condition);

            return $this;
        }

        $this->wrappedCollection->addFieldToFilter($field, $condition);

        return $this;
    }

    private function buildFilterByPrice($condition): void
    {
        if (isset($condition['gteq'])) {
            $field = 'product_' . ProductResource::COLUMN_ONLINE_MIN_PRICE;
            $this->wrappedCollection->addFieldToFilter($field, $condition);
        }

        if (isset($condition['lteq'])) {
            $field = 'product_' . ProductResource::COLUMN_ONLINE_MAX_PRICE;
            $this->wrappedCollection->addFieldToFilter($field, $condition);
        }
    }

    private function addFilterByTag($condition): void
    {
        $errorCode = null;
        if (isset($condition['eq'])) {
            $errorCode = [$condition['eq']];
        } elseif (isset($condition['in'])) {
            $errorCode = $condition['in'];
        }

        if ($errorCode === null) {
            return;
        }

        if (!$this->isAlreadyFilteredByErrorCode) {
            $this->wrappedCollection->joinTable(
                ['tag_product_relation' => $this->tagProductRelationResource->getMainTable()],
                sprintf(
                    '%s = %s',
                    TagProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                    self::PRIMARY_COLUMN,
                ),
                [
                    'tag_product_relation_id' => TagProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                    'tag_product_relation_tag_id' => TagProductRelationResource::COLUMN_TAG_ID,
                ],
            );

            $this->wrappedCollection->joinTable(
                ['tag' => $this->tagResource->getMainTable()],
                sprintf(
                    '%s = tag_product_relation_tag_id',
                    TagResource::COLUMN_ID,
                ),
                ['tag_id' => TagResource::COLUMN_ID],
            );

            $this->isAlreadyFilteredByErrorCode = true;
        }

        $this->wrappedCollection->getSelect()
                                ->where(sprintf('tag.%s in (?)', TagResource::COLUMN_ERROR_CODE), $errorCode);
    }

    // ----------------------------------------

    public function setPageSize($size)
    {
        if ($size === false) {
            $this->isGetAllItemsFromFilter = true;
        }

        $this->wrappedCollection->setPageSize($size);

        return $this;
    }

    public function setCurPage($page)
    {
        $this->wrappedCollection->setCurPage($page);

        return $this;
    }

    public function setOrder($field, $direction = \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
    {
        if ($field === 'product_online_price') {
            if ($direction === \Magento\Framework\Data\Collection::SORT_ORDER_ASC) {
                $field = 'product_online_min_price';
            } else {
                $field = 'product_online_max_price';
            }
        } elseif ($field === 'column_title') {
            $field = 'name';
        }

        $this->wrappedCollection->setOrder($field, $direction);

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->wrappedCollection->getSize();
    }
}
