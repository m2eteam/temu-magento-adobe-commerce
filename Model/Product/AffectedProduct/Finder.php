<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\AffectedProduct;

use M2E\Temu\Model\ResourceModel\Product as ListingProductResource;
use M2E\Temu\Model\ResourceModel\Product\VariantSku as VariantSkuResource;

class Finder
{
    private array $runtimeCache = [];
    private \M2E\Temu\Model\ResourceModel\Product $listingProductResource;
    private \M2E\Temu\Model\ResourceModel\Product\VariantSku $variantSkuResource;
    private \M2E\Temu\Model\ResourceModel\Listing $listingResource;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \M2E\Temu\Model\ResourceModel\Product\CollectionFactory $listingProductCollectionFactory;
    private \M2E\Temu\Model\ResourceModel\Product\VariantSku\CollectionFactory $variantSkuCollectionFactory;

    public function __construct(
        \M2E\Temu\Model\ResourceModel\Product\CollectionFactory $listingProductCollectionFactory,
        \M2E\Temu\Model\ResourceModel\Product\VariantSku $variantSkuResource,
        \M2E\Temu\Model\ResourceModel\Product $listingProductResource,
        \M2E\Temu\Model\ResourceModel\Listing $listingResource,
        \M2E\Temu\Model\ResourceModel\Product\VariantSku\CollectionFactory $variantSkuCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->listingProductResource = $listingProductResource;
        $this->listingResource = $listingResource;
        $this->resourceConnection = $resourceConnection;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->variantSkuCollectionFactory = $variantSkuCollectionFactory;
        $this->variantSkuResource = $variantSkuResource;
    }

    public function find(
        int $magentoProductId,
        array $listingFilters = [],
        array $listingProductFilters = []
    ): \M2E\Temu\Model\Product\AffectedProduct\Collection {
        $filters = [$listingFilters, $listingProductFilters];
        $cacheKey = __METHOD__ . $magentoProductId . sha1(\M2E\Core\Helper\Json::encode($filters));
        $cacheValue = $this->runtimeCache[$cacheKey] ?? null;

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $simpleProductsSelect = $this->getSimpleProductSelect($magentoProductId);
        $simpleProductsSelect = $this->applyListingFilters($simpleProductsSelect, $listingFilters);
        $simpleProductsSelect = $this->applyListingProductFilters($simpleProductsSelect, $listingProductFilters);

        $variantProductSelect = $this->getVariantProductSelect($magentoProductId);
        $variantProductSelect = $this->applyListingFilters($variantProductSelect, $listingFilters);
        $variantProductSelect = $this->applyListingProductFilters($variantProductSelect, $listingProductFilters);

        /** @var array{array{product_id: string, variant_id: ?string}} $affectedDataLines */
        $affectedDataLines = $this->resourceConnection
            ->getConnection()
            ->select()
            ->union([$simpleProductsSelect, $variantProductSelect])
            ->query()
            ->fetchAll();

        $listingProductsSortedById = $this->getListingProducts(
            $this->getUniqueProductIds($affectedDataLines)
        );

        $variantSkusSortedById = $this->getVariantSkuProducts(
            $this->getUniqueVariantSkuIds($affectedDataLines)
        );

        $resultCollection = new \M2E\Temu\Model\Product\AffectedProduct\Collection();

        foreach ($affectedDataLines as $affectedId) {
            $affectedProduct = $listingProductsSortedById[$affectedId['product_id']];
            $affectedVariant = $variantSkusSortedById[(string)$affectedId['variant_id']] ?? null;

            $resultCollection->addResult(
                new \M2E\Temu\Model\Product\AffectedProduct\Product(
                    $affectedProduct,
                    $affectedVariant
                )
            );
        }

        $this->runtimeCache[$cacheKey] = $resultCollection;

        return $resultCollection;
    }

    private function applyListingProductFilters(
        \Magento\Framework\DB\Select $select,
        array $listingProductFilters
    ): \Magento\Framework\DB\Select {
        if (empty($listingProductFilters)) {
            return $select;
        }

        foreach ($listingProductFilters as $column => $value) {
            $condition = is_array($value)
                ? sprintf('listing_product.%s IN(?)', $column)
                : sprintf('listing_product.%s = ?', $column);

            $select->where($condition, $value);
        }

        return $select;
    }

    private function applyListingFilters(
        \Magento\Framework\DB\Select $select,
        array $listingFilters
    ): \Magento\Framework\DB\Select {
        if (empty($listingFilters)) {
            return $select;
        }

        $select->join(
            ['listing' => $this->listingResource->getMainTable()],
            sprintf(
                'listing.%s = listing_product.%s',
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_ID,
                \M2E\Temu\Model\ResourceModel\Product::COLUMN_LISTING_ID,
            ),
            [],
        );

        foreach ($listingFilters as $column => $value) {
            $condition = is_array($value)
                ? sprintf('listing.%s IN(?)', $column)
                : sprintf('listing.%s = ?', $column);

            $select->where($condition, $value);
        }

        return $select;
    }

    /**
     * @return \M2E\Temu\Model\Product[]
     */
    private function getListingProducts(array $listingProductIds): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(ListingProductResource::COLUMN_ID, ['in' => $listingProductIds]);

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[$item->getId()] = $item;
        }

        return $result;
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    private function getVariantSkuProducts(array $variantSkuIds): array
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(VariantSkuResource::COLUMN_ID, ['in' => $variantSkuIds]);

        $result = [];
        foreach ($collection->getItems() as $item) {
            $result[$item->getId()] = $item;
        }

        return $result;
    }

    private function getSimpleProductSelect(int $magentoProductId): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection->getConnection()->select();

        $select->distinct();
        $select->from(
            ['listing_product' => $this->listingProductResource->getMainTable()],
            [
                'product_id' => ListingProductResource::COLUMN_ID,
                'variant_id' => new \Zend_Db_Expr('NULL'),
            ],
        );
        $select->where(
            sprintf('listing_product.%s = ?', ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID),
            $magentoProductId,
        );

        return $select;
    }

    private function getVariantProductSelect(int $magentoProductId): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection->getConnection()->select();

        $select->distinct();
        $select->from(
            ['listing_product' => $this->listingProductResource->getMainTable()],
            [
                'product_id' => ListingProductResource::COLUMN_ID,
            ]
        );
        $select->joinInner(
            ['variant' => $this->variantSkuResource->getMainTable()],
            sprintf(
                'listing_product.%s = variant.%s',
                ListingProductResource::COLUMN_ID,
                VariantSkuResource::COLUMN_PRODUCT_ID
            ),
            [
                'variant_id' => VariantSkuResource::COLUMN_ID,
            ]
        );
        $select->where(
            sprintf('variant.%s = ?', ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID),
            $magentoProductId,
        );
        $select->where(sprintf('listing_product.%s = 0', ListingProductResource::COLUMN_IS_SIMPLE));

        return $select;
    }

    private function getUniqueVariantSkuIds(array $affectedDataLines): array
    {
        return array_unique(array_column($affectedDataLines, 'variant_id'));
    }

    private function getUniqueProductIds(array $affectedDataLines): array
    {
        return array_unique(array_column($affectedDataLines, 'product_id'));
    }
}
