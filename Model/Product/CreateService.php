<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product;

class CreateService
{
    private const MAX_CONFIGURABLE_ATTRIBUTE_COUNT = 2;

    private \M2E\Temu\Model\ProductFactory $listingProductFactory;
    private \M2E\Temu\Model\Product\VariantSkuFactory $variantSkuFactory;
    private Repository $listingProductRepository;
    private \Magento\Framework\App\ResourceConnection $resource;
    private \M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository;
    private \M2E\Temu\Model\Product\VariantSku\DeletedFactory $deletedVariantSkuFactory;

    public function __construct(
        \M2E\Temu\Model\ProductFactory $listingProductFactory,
        \M2E\Temu\Model\Product\VariantSkuFactory $variantSkuFactory,
        Repository $listingProductRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository,
        \M2E\Temu\Model\Product\VariantSku\DeletedFactory $deletedVariantSkuFactory
    ) {
        $this->listingProductFactory = $listingProductFactory;
        $this->variantSkuFactory = $variantSkuFactory;
        $this->listingProductRepository = $listingProductRepository;
        $this->resource = $resource;
        $this->deletedVariantSkuRepository = $deletedVariantSkuRepository;
        $this->deletedVariantSkuFactory = $deletedVariantSkuFactory;
    }

    public function create(
        \M2E\Temu\Model\Listing $listing,
        \M2E\Temu\Model\Magento\Product $m2eMagentoProduct,
        ?int $categoryDictionaryId,
        ?\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct = null
    ): \M2E\Temu\Model\Product {
        $this->checkSupportedMagentoType($m2eMagentoProduct);

        $listingProduct = $this->listingProductFactory->create(
            $listing,
            $m2eMagentoProduct->getProductId(),
            $m2eMagentoProduct->isSimpleType(),
        );

        if ($unmanagedProduct !== null) {
            $listingProduct->fillFromUnmanagedProduct($unmanagedProduct);
        }

        if ($categoryDictionaryId !== null) {
            $listingProduct->setTemplateCategoryId($categoryDictionaryId);
        }

        if ($m2eMagentoProduct->isConfigurableType()) {
            $listingProduct->setVariationAttributes(
                $this->collectVariationAttributes($m2eMagentoProduct)
            );
        }

        $transaction = $this->resource->getConnection()->beginTransaction();
        try {
            $this->listingProductRepository->create($listingProduct);
            $variants = $this->createVariants($listingProduct, $m2eMagentoProduct, $unmanagedProduct);

            $this->listingProductRepository->createVariantsSku($variants);
            if ($unmanagedProduct !== null) {
                $this->createDeletedVariants($listingProduct, $unmanagedProduct);
            }
            $listingProduct->recalculateOnlineDataByVariants();
            $this->listingProductRepository->save($listingProduct);
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        $transaction->commit();

        return $listingProduct;
    }

    private function createVariants(
        \M2E\Temu\Model\Product $listingProduct,
        \M2E\Temu\Model\Magento\Product $m2eMagentoProduct,
        ?\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct
    ): array {
        $unmanagedVariants = [];
        if ($unmanagedProduct !== null) {
            foreach ($unmanagedProduct->getVariants() as $variant) {
                if ($variant->hasMagentoProductId()) {
                    $unmanagedVariants[$variant->getMagentoProductId()] = $variant;
                }
            }
        }

        if ($m2eMagentoProduct->isSimpleType()) {
            return [
                $this->createVariantEntity(
                    $listingProduct,
                    $m2eMagentoProduct,
                    $unmanagedVariants[$m2eMagentoProduct->getProductId()] ?? null
                ),
            ];
        }

        $variants = [];
        foreach ($m2eMagentoProduct->getConfigurableChildren() as $child) {
            if ($unmanagedProduct !== null) {
                foreach ($unmanagedProduct->getVariants() as $unmanagedVariant) {
                    if (!$unmanagedVariant->hasMagentoProductId()) {
                        continue;
                    }

                    $variants[] = $this->createVariantEntity(
                        $listingProduct,
                        $child,
                        $unmanagedVariant
                    );
                }

                return $variants;
            }

            $variants[] = $this->createVariantEntity(
                $listingProduct,
                $child,
                $unmanagedVariants[$child->getProductId()] ?? null
            );
        }

        return $variants;
    }

    private function createVariantEntity(
        \M2E\Temu\Model\Product $listingProduct,
        \M2E\Temu\Model\Magento\Product $m2eMagentoProduct,
        ?\M2E\Temu\Model\UnmanagedProduct\VariantSku $unmanagedVariantSku = null
    ): VariantSku {
        $variantSku = $this->variantSkuFactory->create();
        $variantSku->init($listingProduct, $m2eMagentoProduct->getProductId());

        if ($unmanagedVariantSku !== null) {
            $variantSku->fillFromUnmanagedVariant($unmanagedVariantSku);
        }

        if ($listingProduct->isSimple()) {
            return $variantSku;
        }

        $variationData = new \M2E\Temu\Model\Product\VariantSku\Dto\VariationData();
        foreach ($listingProduct->getVariationAttributes()->getItems() as $variationAttributeItem) {
            $variationData->add(
                new \M2E\Temu\Model\Product\VariantSku\Dto\VariationDataItem(
                    $variationAttributeItem->getAttributeCode(),
                    $variationAttributeItem->getName(),
                    $m2eMagentoProduct->getAttributeValue($variationAttributeItem->getAttributeCode())
                )
            );
        }

        $variantSku->setVariationData($variationData);

        return $variantSku;
    }

    // ----------------------------------------

    private function checkSupportedMagentoType(\M2E\Temu\Model\Magento\Product $m2eMagentoProduct): void
    {
        if (!$this->isSupportedMagentoProductType($m2eMagentoProduct)) {
            throw new \M2E\Temu\Model\Exception\Logic(
                (string)__(
                    sprintf('Unsupported magento product type - %s', $m2eMagentoProduct->getTypeId()),
                ),
            );
        }
    }

    private function isSupportedMagentoProductType(\M2E\Temu\Model\Magento\Product $ourMagentoProduct): bool
    {
        return $ourMagentoProduct->isSimpleType() || $ourMagentoProduct->isConfigurableType();
    }

    private function collectVariationAttributes(
        \M2E\Temu\Model\Magento\Product $m2eMagentoProduct
    ): Dto\VariationAttributes {
        $configurableAttributes = $m2eMagentoProduct->getConfigurableAttributes();

        if (count($configurableAttributes) > self::MAX_CONFIGURABLE_ATTRIBUTE_COUNT) {
            $configurableAttributes = array_slice(
                $configurableAttributes,
                0,
                self::MAX_CONFIGURABLE_ATTRIBUTE_COUNT
            );
        }

        $variationAttributes = new \M2E\Temu\Model\Product\Dto\VariationAttributes();
        foreach ($configurableAttributes as $configurableAttribute) {
            $variationAttributes->addItem(
                new \M2E\Temu\Model\Product\Dto\VariationAttributeItem(
                    $configurableAttribute->getAttributeCode(),
                    $configurableAttribute->getDefaultFrontendLabel()
                )
            );
        }

        return $variationAttributes;
    }

    private function createDeletedVariants(
        \M2E\Temu\Model\Product $listingProduct,
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct
    ) {
        foreach ($unmanagedProduct->getVariants() as $unmanagedVariant) {
            if ($unmanagedVariant->hasMagentoProductId()) {
                continue;
            }

            $deletedVariant = $this->deletedVariantSkuFactory
                ->create()
                ->setProductId($listingProduct->getId())
                ->initFromUnmanagedVariant($unmanagedVariant);

            $this->deletedVariantSkuRepository->create($deletedVariant);
        }
    }
}
