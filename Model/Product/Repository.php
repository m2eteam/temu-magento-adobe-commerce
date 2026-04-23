<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product;

use M2E\Temu\Model\ResourceModel\Listing as ListingResource;
use M2E\Temu\Model\ResourceModel\Product as ProductResource;
use M2E\Temu\Model\ResourceModel\Product\VariantSku as VariantSkuResource;
use M2E\Temu\Model\ResourceModel\InventorySync\ReceivedProduct as InventorySyncReceivedProductResource;

class Repository
{
    private ProductResource $productResource;
    private ProductResource\CollectionFactory $productCollectionFactory;
    private \M2E\Temu\Model\ProductFactory $productFactory;
    private \M2E\Temu\Model\ResourceModel\Listing $listingResource;
    private \M2E\Temu\Model\Product\AffectedProduct\Finder $affectedProductFinder;
    private \M2E\Temu\Helper\Module\Database\Structure $dbStructureHelper;
    /** @var \M2E\Temu\Model\ResourceModel\InventorySync\ReceivedProduct */
    private InventorySyncReceivedProductResource $inventorySyncReceivedProductResource;
    private \M2E\Temu\Model\ResourceModel\Product\VariantSku $variantSkuResource;
    private \M2E\Temu\Model\ResourceModel\Product\VariantSku\CollectionFactory $variantSkuCollectionFactory;
    private \M2E\Temu\Model\Product\VariantSkuFactory $variantSkuFactory;

    public function __construct(
        \M2E\Temu\Model\ResourceModel\Listing $listingResource,
        \M2E\Temu\Model\ResourceModel\Product\VariantSku $variantSkuResource,
        ProductResource $productResource,
        \M2E\Temu\Model\ResourceModel\InventorySync\ReceivedProduct $inventorySyncReceivedProductResource,
        ProductResource\CollectionFactory $productCollectionFactory,
        \M2E\Temu\Model\ResourceModel\Product\VariantSku\CollectionFactory  $variantSkuCollectionFactory,
        \M2E\Temu\Model\ProductFactory $productFactory,
        \M2E\Temu\Model\Product\VariantSkuFactory $variantSkuFactory,
        \M2E\Temu\Model\Product\AffectedProduct\Finder $affectedProductFinder,
        \M2E\Temu\Helper\Module\Database\Structure $dbStructureHelper
    ) {
        $this->productResource = $productResource;
        $this->variantSkuResource = $variantSkuResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->variantSkuCollectionFactory = $variantSkuCollectionFactory;
        $this->productFactory = $productFactory;
        $this->variantSkuFactory = $variantSkuFactory;
        $this->listingResource = $listingResource;
        $this->affectedProductFinder = $affectedProductFinder;
        $this->dbStructureHelper = $dbStructureHelper;
        $this->inventorySyncReceivedProductResource = $inventorySyncReceivedProductResource;
    }

    public function create(\M2E\Temu\Model\Product $product): void
    {
        $this->productResource->save($product);
    }

    public function save(
        \M2E\Temu\Model\Product $product
    ): \M2E\Temu\Model\Product {
        $this->productResource->save($product);

        return $product;
    }

    public function find(int $id): ?\M2E\Temu\Model\Product
    {
        $product = $this->productFactory->createEmpty();
        $this->productResource->load($product, $id);

        if ($product->isObjectNew()) {
            return null;
        }

        return $product;
    }

    public function get(int $id): \M2E\Temu\Model\Product
    {
        $product = $this->find($id);
        if ($product === null) {
            throw new \M2E\Temu\Model\Exception\Logic(sprintf('Listing Product with id "%s" not found.', $id));
        }

        return $product;
    }

    public function getProductsByMagentoProductId(
        int $magentoProductId,
        array $listingFilters = [],
        array $productFilters = []
    ): \M2E\Temu\Model\Product\AffectedProduct\Collection {
        return $this->affectedProductFinder->find(
            $magentoProductId,
            $listingFilters,
            $productFilters,
        );
    }

    public function delete(\M2E\Temu\Model\Product $product): void
    {
        $this->productResource->delete($product);
    }

    public function deleteVariantSku(\M2E\Temu\Model\Product\VariantSku $variantSku): void
    {
        $this->variantSkuResource->delete($variantSku);
    }

    // ----------------------------------------

    /**
     * @return \M2E\Temu\Model\Product[]
     */
    public function findByListing(\M2E\Temu\Model\Listing $listing): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(
            ProductResource::COLUMN_LISTING_ID,
            ['eq' => $listing->getId()],
        );

        return array_values($collection->getItems());
    }

    public function findByListingAndMagentoProductId(
        \M2E\Temu\Model\Listing $listing,
        int $magentoProductId
    ): ?\M2E\Temu\Model\Product {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(
            ProductResource::COLUMN_LISTING_ID,
            ['eq' => $listing->getId()],
        );
        $collection->addFieldToFilter(
            ProductResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['eq' => $magentoProductId],
        );

        $product = $collection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        return $product;
    }

    public function findProductsByMagentoSku(
        string $sku
    ): array {
        $collection = $this->productCollectionFactory->create();
        $entityTableName = $this->dbStructureHelper->getTableNameWithPrefix('catalog_product_entity');

        $collection->getSelect()
                   ->join(
                       ['cpe' => $entityTableName],
                       sprintf(
                           'cpe.entity_id = `main_table`.%s',
                           ProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                       ),
                       [],
                   );
        $collection->addFieldToFilter(
            'cpe.sku',
            ['like' => '%' . $sku . '%'],
        );

        return $collection->getItems();
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function findVariantSkusByMagentoProductId(int $magentoProductId): array
    {
        return $this->findVariantSkusByMagentoProductIds([$magentoProductId]);
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function findVariantSkusByMagentoProductIds(array $magentoProductIds): array
    {
        if (empty($magentoProductIds)) {
            return [];
        }

        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(
            VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['in' => $magentoProductIds],
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function findActiveVariantSkusByMagentoProductIds(array $magentoProductIds): array
    {
        if (empty($magentoProductIds)) {
            return [];
        }

        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(
            VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['in' => $magentoProductIds],
        )->addFieldToFilter(VariantSkuResource::COLUMN_STATUS, \M2E\Temu\Model\Product::STATUS_LISTED);

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\Temu\Model\Product[]
     */
    public function findByIds(array $productsIds): array
    {
        if (empty($productsIds)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(
            ProductResource::COLUMN_ID,
            ['in' => $productsIds],
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\Temu\Model\Product[]
     */
    public function findByMagentoProductId(int $magentoProductId): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(
            ProductResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['eq' => $magentoProductId],
        );

        return array_values($collection->getItems());
    }

    /**
     * @param array $channelProductIds
     * @param int $accountId
     * @param int|null $listingId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findByChannelIds(
        array $channelProductIds,
        int $accountId,
        ?int $listingId = null
    ): array {
        if (empty($channelProductIds)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();

        $collection->addFieldToFilter(
            sprintf('main_table.%s', ProductResource::COLUMN_CHANNEL_PRODUCT_ID),
            ['in' => $channelProductIds],
        );

        $collection
            ->join(
                ['l' => $this->listingResource->getMainTable()],
                sprintf(
                    '`l`.%s = `main_table`.%s',
                    ListingResource::COLUMN_ID,
                    ProductResource::COLUMN_LISTING_ID,
                ),
                [],
            )
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId);

        if ($listingId !== null) {
            $collection->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ID), $listingId);
        }

        return array_values($collection->getItems());
    }

    /**
     * @param array $channelProductsSkus
     * @param int $accountId
     * @param int|null $listingId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findBySkus(
        array $channelProductsSkus,
        int $accountId,
        ?int $listingId = null
    ): array {
        if (empty($channelProductsSkus)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();

        $collection->addFieldToFilter(
            sprintf('main_table.%s', ProductResource::COLUMN_ONLINE_SKU),
            ['in' => $channelProductsSkus],
        );

        $collection
            ->join(
                ['l' => $this->listingResource->getMainTable()],
                sprintf(
                    '`l`.%s = `main_table`.%s',
                    ListingResource::COLUMN_ID,
                    ProductResource::COLUMN_LISTING_ID,
                ),
                [],
            )
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId);

        if ($listingId !== null) {
            $collection->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ID), $listingId);
        }

        return array_values($collection->getItems());
    }

    public function createVariantSku(\M2E\Temu\Model\Product\VariantSku $variantSku): void
    {
        $this->variantSkuResource->save($variantSku);
    }

    /**
     * @param \M2E\Temu\Model\Product\VariantSku[] $variantsSku
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createVariantsSku(array $variantsSku): void
    {
        foreach ($variantsSku as $variantSku) {
            $this->variantSkuResource->save($variantSku);
        }
    }

    /**
     * @param \M2E\Temu\Model\Product\VariantSku[] $variantsSku
     *
     * @return void
     */
    public function saveVariantsSku(array $variantsSku): void
    {
        foreach ($variantsSku as $variantSku) {
            $this->saveVariantSku($variantSku);
        }
    }

    public function saveVariantSku(VariantSku $variantSku): void
    {
        $this->variantSkuResource->save($variantSku);
    }

    /**
     * @param \M2E\Temu\Model\Product $product
     *
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function findVariantsByProduct(\M2E\Temu\Model\Product $product): array
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(VariantSkuResource::COLUMN_PRODUCT_ID, $product->getId());

        $items = array_values($collection->getItems());
        foreach ($items as $item) {
            $item->initProduct($product);
        }

        return $items;
    }

    public function findVariantSkuByChannelProductIdAndSkuId(string $channelProductId, string $skuId): ?VariantSku
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection
            ->join(
                ['p' => $this->productResource->getMainTable()],
                sprintf(
                    'main_table.%s = p.%s',
                    VariantSkuResource::COLUMN_PRODUCT_ID,
                    ProductResource::COLUMN_ID,
                ),
                [],
            )
            ->addFieldToFilter(sprintf('p.%s', $this->productResource::COLUMN_CHANNEL_PRODUCT_ID), $channelProductId)
            ->addFieldToFilter(sprintf('main_table.%s', VariantSkuResource::COLUMN_SKU_ID), $skuId)
            ->setPageSize(1);

        $variantSku = $collection->getFirstItem();
        if ($variantSku->isObjectNew()) {
            return null;
        }

        return $variantSku;
    }

    public function findVariantBySkuAndAccount(string $sku, int $accountId): ?VariantSku
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection
            ->join(
                ['p' => $this->productResource->getMainTable()],
                sprintf(
                    'main_table.%s = p.%s',
                    VariantSkuResource::COLUMN_PRODUCT_ID,
                    ProductResource::COLUMN_ID,
                ),
                [],
            );

        $collection->join(
            ['l' => $this->listingResource->getMainTable()],
            sprintf(
                '`l`.%s = `p`.%s',
                ListingResource::COLUMN_ID,
                ProductResource::COLUMN_LISTING_ID,
            ),
            [],
        );

        $collection
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId)
            ->addFieldToFilter(sprintf('p.%s', VariantSkuResource::COLUMN_ONLINE_SKU), $sku);

        $variantSku = $collection->getFirstItem();
        if ($variantSku->isObjectNew()) {
            return null;
        }

        return $variantSku;
    }

    // ----------------------------------------

    public function getCountListedProductsForListing(\M2E\Temu\Model\Listing $listing): int
    {
        $collection = $this->productCollectionFactory->create();
        $collection
            ->addFieldToFilter(ProductResource::COLUMN_LISTING_ID, $listing->getId())
            ->addFieldToFilter(ProductResource::COLUMN_STATUS, \M2E\Temu\Model\Product::STATUS_LISTED);

        return (int)$collection->getSize();
    }

    public function hasListedProducts(): bool
    {
        return $this->getCountOfListedProducts() > 0;
    }

    public function getCountOfListedProducts(): int
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(ProductResource::COLUMN_STATUS, \M2E\Temu\Model\Product::STATUS_LISTED);

        return (int)$collection->getSize();
    }

    public function getCountOfNotListedProducts(): int
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(ProductResource::COLUMN_STATUS, ['neq' => \M2E\Temu\Model\Product::STATUS_LISTED]);

        return (int)$collection->getSize();
    }

    public function getTotalCountOfListingProducts(): int
    {
        $collection = $this->productCollectionFactory->create();

        return (int)$collection->getSize();
    }

    /**
     * @param int $listingId
     *
     * @return int[]
     */
    public function findMagentoProductIdsByListingId(int $listingId): array
    {
        $collection = $this->productCollectionFactory->create();

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);

        $collection
            ->addFieldToSelect(ProductResource::COLUMN_MAGENTO_PRODUCT_ID)
            ->addFieldToSelect(ProductResource::COLUMN_ID) // for load collection
            ->addFieldToFilter(ProductResource::COLUMN_LISTING_ID, $listingId);

        $result = [];
        foreach ($collection->getItems() as $product) {
            $result[] = $product->getMagentoProductId();
        }

        return $result;
    }

    /**
     * @param int $storeId
     *
     * @return \M2E\Temu\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductCollectionByStoreId(int $storeId): ProductResource\Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->joinLeft(
            ['listing' => $this->listingResource->getMainTable()],
            sprintf(
                'listing.%s = main_table.%s',
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_ID,
                \M2E\Temu\Model\ResourceModel\Product::COLUMN_LISTING_ID
            ),
            [\M2E\Temu\Model\ResourceModel\Listing::COLUMN_STORE_ID]
        );
        $collection->addFieldToFilter(
            \M2E\Temu\Model\ResourceModel\Listing::COLUMN_STORE_ID,
            $storeId
        );

        return $collection;
    }

    /**
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     *
     * @return \M2E\Temu\Model\Product[]
     */
    public function massActionSelectedProducts(\Magento\Ui\Component\MassAction\Filter $filter): array
    {
        $collection = $this->productCollectionFactory->create();
        $filter->getCollection($collection);

        return array_values($collection->getItems());
    }

    /**
     * @return int[]
     */
    public function findRemovedMagentoProductIds(int $limit): array
    {
        $collection = $this->productCollectionFactory->create();

        $collection->getSelect()
                   ->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()
                   ->columns(
                       ProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                   );
        $collection->getSelect()
                   ->distinct();

        $entityTableName = $this->dbStructureHelper->getTableNameWithPrefix('catalog_product_entity');

        $collection->getSelect()
                   ->joinLeft(
                       ['cpe' => $entityTableName],
                       sprintf(
                           'cpe.entity_id = `main_table`.%s',
                           ProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                       ),
                       [],
                   );

        $collection->getSelect()
                   ->where('cpe.entity_id IS NULL');
        $collection->getSelect()
                   ->limit($limit);

        $result = [];
        foreach ($collection->toArray()['items'] ?? [] as $row) {
            $result[] = (int)$row[ProductResource::COLUMN_MAGENTO_PRODUCT_ID];
        }

        return $result;
    }

    /**
     * @param int $accountId
     * @param \DateTime $inventorySyncProcessingStartDate
     *
     * @return \M2E\Temu\Model\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findRemovedFromChannel(
        \DateTime $inventorySyncProcessingStartDate,
        int $accountId
    ): array {
        $collection = $this->productCollectionFactory->create();

        $collection->join(
            ['l' => $this->listingResource->getMainTable()],
            sprintf(
                '`l`.%s = `main_table`.%s',
                ListingResource::COLUMN_ID,
                ProductResource::COLUMN_LISTING_ID,
            ),
            [],
        );

        $collection->joinLeft(
            [
                'isrp' => $this->inventorySyncReceivedProductResource->getMainTable(),
            ],
            implode(' AND ', [
                sprintf(
                    '`isrp`.%s = `main_table`.%s',
                    InventorySyncReceivedProductResource::COLUMN_CHANNEL_PRODUCT_ID,
                    ProductResource::COLUMN_CHANNEL_PRODUCT_ID,
                ),
                sprintf(
                    '`isrp`.%s = `l`.%s',
                    InventorySyncReceivedProductResource::COLUMN_ACCOUNT_ID,
                    ListingResource::COLUMN_ACCOUNT_ID,
                ),
            ]),
            [],
        );

        $collection
            ->addFieldToFilter(
                sprintf('main_table.%s', ProductResource::COLUMN_STATUS),
                ['neq' => \M2E\Temu\Model\Product::STATUS_NOT_LISTED],
            )
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId)
            ->addFieldToFilter('isrp.id', ['null' => true]);
        /**
         * Excluding listing products created after current inventory sync processing start date
         */
        $collection->getSelect()->where(
            sprintf('main_table.%s ', ProductResource::COLUMN_ID)
            . 'NOT IN (?)',
            $this->getExcludedByDateSubSelect($inventorySyncProcessingStartDate)
        );

        return array_values($collection->getItems());
    }

    private function getExcludedByDateSubSelect(\DateTime $inventorySyncProcessingStartDate): \Zend_Db_Expr
    {
        return new \Zend_Db_Expr(
            sprintf(
                'SELECT `%s` FROM `%s` WHERE `%s`=%s AND `%s` > "%s"',
                ProductResource::COLUMN_ID,
                $this->productResource->getMainTable(),
                ProductResource::COLUMN_STATUS,
                \M2E\Temu\Model\Product::STATUS_LISTED,
                ProductResource::COLUMN_STATUS_CHANGE_DATE,
                $inventorySyncProcessingStartDate->format('Y-m-d H:i:s'),
            )
        );
    }

    // ----------------------------------------

    public function findIdsByListingId(int $listingId): array
    {
        if (empty($listingId)) {
            return [];
        }

        $select = $this->productResource->getConnection()
                                        ->select()
                                        ->from($this->productResource->getMainTable(), 'id')
                                        ->where('listing_id = ?', $listingId);

        return array_column($select->query()->fetchAll(), 'id');
    }

    public function updateLastBlockingErrorDate(array $productIds, \DateTime $dateTime): void
    {
        if (empty($productIds)) {
            return;
        }

        $this->productResource->getConnection()->update(
            $this->productResource->getMainTable(),
            [ProductResource::COLUMN_LAST_BLOCKING_ERROR_DATE => $dateTime->format('Y-m-d H:i:s')],
            ['id IN (?)' => $productIds]
        );
    }

    public function getIds(int $fromId, int $limit): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter('id', ['gt' => $fromId]);
        $collection->getSelect()->order(['id ASC']);
        $collection->getSelect()->limit($limit);

        return array_map('intval', $collection->getColumnValues('id'));
    }

    /**
     * @param int $templateCategoryId
     *
     * @return int
     */
    public function getCountProductsByCategoryId(int $templateCategoryId): int
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(ProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $templateCategoryId);

        return (int)$collection->getSize();
    }

    /**
     * @param int $templateCategoryId
     * @param int|null $limit
     *
     * @return \M2E\Temu\Model\Product[]
     */
    public function findProductsForValidateCategoryAttributes(
        int $templateCategoryId,
        int $limit
    ): array {
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter(ProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $templateCategoryId);
        $collection->addFieldToFilter(ProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES, ['null' => true]);
        $collection->addFieldToFilter(ProductResource::COLUMN_STATUS, \M2E\Temu\Model\Product::STATUS_NOT_LISTED);

        $collection->getSelect()->limit($limit);

        return array_values($collection->getItems());
    }

    public function resetCategoryAttributesValidationData(int $categoryId): void
    {
        $this->productResource
            ->getConnection()
            ->update(
                $this->productResource->getMainTable(),
                [
                    ProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES => null,
                    ProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS => null,
                ],
                [
                    sprintf('%s = %d', ProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $categoryId),
                ],
            );
    }
}
