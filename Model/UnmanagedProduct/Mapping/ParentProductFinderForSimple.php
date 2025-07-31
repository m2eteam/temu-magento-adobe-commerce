<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\Mapping;

use M2E\Temu\Model\Magento\Product as ProductModel;

class ParentProductFinderForSimple
{
    private MagentoProductFinder $productFinder;
    private \M2E\Temu\Helper\Magento\Product $magentoProductHelper;

    public function __construct(
        \M2E\Temu\Helper\Magento\Product $magentoProductHelper,
        MagentoProductFinder $magentoProductFinder
    ) {
        $this->magentoProductHelper = $magentoProductHelper;
        $this->productFinder = $magentoProductFinder;
    }

    public function findMagentoProduct(
        \M2E\Temu\Model\UnmanagedProduct $unmanaged
    ): ?\Magento\Catalog\Model\Product {
        $mappingTypes = $unmanaged
            ->getAccount()
            ->getUnmanagedListingSettings()
            ->getMappingTypesByPriority();

        foreach ($mappingTypes as $type) {
            $magentoProduct = $this->findMagentoProductIdByMappingType($type, $unmanaged);

            if ($magentoProduct === null) {
                continue;
            }

            if ($this->isProductTypeCompatible($magentoProduct)) {
                return $magentoProduct;
            }
        }

        return null;
    }

    private function findMagentoProductIdByMappingType(
        string $type,
        \M2E\Temu\Model\UnmanagedProduct $unmanaged
    ): ?\Magento\Catalog\Model\Product {
        $product = null;

        if (
            !in_array($type, [
                \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_SKU,
                \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_TITLE,
            ])
        ) {
            throw new \M2E\Temu\Model\Exception\Logic(
                sprintf('Unknown mapping type "%s"', $type)
            );
        }

        if ($type === \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_SKU) {
            $product = $this->findSkuMappedMagentoProductId($unmanaged);
        }

        if ($type === \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_TITLE) {
            $product = $this->findTitleMappedMagentoProductId($unmanaged);
        }

        if ($product === null) {
            return null;
        }

        if (!$this->isMagentoProductTypeAllowed($product->getTypeId())) {
            return null;
        }

        return $product;
    }

    private function findSkuMappedMagentoProductId(
        \M2E\Temu\Model\UnmanagedProduct $unmanaged
    ): ?\Magento\Catalog\Model\Product {
        $firstSkuFromVariants = trim($unmanaged->getFirstSkuFromVariants());

        if (empty($firstSkuFromVariants)) {
            return null;
        }

        $settings = $unmanaged->getAccount()->getUnmanagedListingSettings();

        if ($settings->isMappingBySkuModeByProductId()) {
            return $this->productFinder
                ->findProductByProductId($firstSkuFromVariants);
        }

        if ($settings->isMappingBySkuModeBySku()) {
            return $this->productFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                'sku',
                $firstSkuFromVariants
            );
        }

        if ($settings->isMappingBySkuModeByAttribute()) {
            return $this->productFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                $settings->getMappingAttributeBySku() ?: '',
                $firstSkuFromVariants
            );
        }

        return null;
    }

    private function findTitleMappedMagentoProductId(
        \M2E\Temu\Model\UnmanagedProduct $unmanaged
    ): ?\Magento\Catalog\Model\Product {
        $unmanagedProductTitle = trim($unmanaged->getTitle());

        if (empty($unmanagedProductTitle)) {
            return null;
        }

        $settings = $unmanaged->getAccount()->getUnmanagedListingSettings();

        if ($settings->isMappingByTitleModeByProductName()) {
            return $this->productFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                'name',
                $unmanagedProductTitle
            );
        }

        if ($settings->isMappingByTitleModeByAttribute()) {
            return $this->productFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                $settings->getMappingAttributeByTitle(),
                $unmanagedProductTitle
            );
        }

        return null;
    }

    private function isMagentoProductTypeAllowed($type): bool
    {
        $allowedTypes = [
            ProductModel::TYPE_SIMPLE_ORIGIN,
            ProductModel::TYPE_VIRTUAL_ORIGIN,
        ];

        return in_array($type, $allowedTypes);
    }

    private function isProductTypeCompatible(
        \Magento\Catalog\Model\Product $magentoProduct
    ): bool {
        if ($this->magentoProductHelper->isSimpleType($magentoProduct->getTypeId())) {
            return true;
        }

        return false;
    }
}
