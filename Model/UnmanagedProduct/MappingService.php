<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct;

use M2E\Temu\Model\Magento\Product as ProductModel;

class MappingService
{
    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository;
    private \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory;
    private \M2E\Temu\Helper\Magento\Product $magentoProductHelper;
    private \M2E\Temu\Model\UnmanagedProduct\VariantSku\SalesAttributeFactory $salesAttributeFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Mapping\ParentProductFinderForSimple $parentProductFinderForSimple;
    private \M2E\Temu\Model\UnmanagedProduct\Mapping\ParentProductFinderForConfigurable $parentProductFinderForConfigurable;
    private \M2E\Temu\Model\UnmanagedProduct\Mapping\VariantsMapping $variantsMapping;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory,
        \M2E\Temu\Helper\Magento\Product $magentoProductHelper,
        \M2E\Temu\Model\UnmanagedProduct\VariantSku\SalesAttributeFactory $salesAttributeFactory,
        \M2E\Temu\Model\UnmanagedProduct\Mapping\ParentProductFinderForSimple $parentProductFinderForSimple,
        \M2E\Temu\Model\UnmanagedProduct\Mapping\ParentProductFinderForConfigurable $parentProductFinderForConfigurable,
        \M2E\Temu\Model\UnmanagedProduct\Mapping\VariantsMapping $variantsMapping
    ) {
        $this->productFactory = $productFactory;
        $this->unmanagedRepository = $unmanagedRepository;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->magentoProductHelper = $magentoProductHelper;
        $this->salesAttributeFactory = $salesAttributeFactory;
        $this->parentProductFinderForSimple = $parentProductFinderForSimple;
        $this->parentProductFinderForConfigurable = $parentProductFinderForConfigurable;
        $this->variantsMapping = $variantsMapping;
    }

    /**
     * @param \M2E\Temu\Model\UnmanagedProduct[] $unmanagedProducts
     *
     * @return bool
     */
    public function autoMapUnmanagedProducts(array $unmanagedProducts): bool
    {
        $unmanagedProductsFiltered = array_filter($unmanagedProducts, function ($unmanaged) {
            return !$unmanaged->hasMagentoProductId();
        });

        if (empty($unmanagedProductsFiltered)) {
            return false;
        }

        $result = true;
        foreach ($unmanagedProductsFiltered as $unmanaged) {
            if (!$this->autoMapUnmanagedProduct($unmanaged)) {
                $result = false;
            }
        }

        return $result;
    }

    private function autoMapUnmanagedProduct(\M2E\Temu\Model\UnmanagedProduct $unmanaged): bool
    {
        if ($unmanaged->hasMagentoProductId()) {
            return false;
        }

        if (!$unmanaged->getAccount()->getUnmanagedListingSettings()->isMappingEnabled()) {
            return false;
        }

        if ($unmanaged->isSimple()) {
            $magentoProduct = $this->parentProductFinderForSimple->findMagentoProduct($unmanaged);
        } else {
            $magentoProduct = $this->parentProductFinderForConfigurable->findMagentoProduct($unmanaged);
        }

        if ($magentoProduct === null) {
            return false;
        }

        return $this->mapProduct($unmanaged, $magentoProduct);
    }

    // ----------------------------------------

    // ----------------------------------------

    private function mapProduct(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ): bool {
        if ($unmanagedProduct->isSimple()) {
            $this->variantsMapping->mapSimpleVariants($unmanagedProduct, $magentoProduct);

            return true;
        }

        $this->variantsMapping->mapConfigurableVariants($unmanagedProduct, $magentoProduct);
        $unmanagedProduct->mapToMagentoProduct((int)$magentoProduct->getId());
        $this->unmanagedRepository->save($unmanagedProduct);

        return true;
    }

    // ----------------------------------------

    public function manualMapProduct(int $unmanagedId, int $productId): bool
    {
        $unmanagedProduct = $this->unmanagedRepository->findById($unmanagedId);
        if (!$unmanagedProduct) {
            return false;
        }

        $magentoProduct = $this->magentoProductFactory->createByProductId($productId);

        return $this->mapProduct($unmanagedProduct, $magentoProduct->getProduct());
    }

    public function unmapProduct(\M2E\Temu\Model\UnmanagedProduct $product): void
    {
        $product->unmapFromMagentoProduct();
        $this->unmanagedRepository->save($product);

        foreach ($product->getVariants() as $variant) {
            $variant->unmapVariant();
            $this->unmanagedRepository->saveVariant($variant);
        }
    }

    public function unmapVariants(array $variants): void
    {
        foreach ($variants as $variant) {
            $variant->unmapVariant();
            $this->unmanagedRepository->saveVariant($variant);
        }
    }
}
