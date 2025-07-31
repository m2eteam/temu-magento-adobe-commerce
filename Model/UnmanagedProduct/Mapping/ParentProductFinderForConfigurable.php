<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\Mapping;

use M2E\Temu\Model\Magento\Product as ProductModel;

class ParentProductFinderForConfigurable
{
    private \M2E\Temu\Helper\Magento\Product $magentoProductHelper;
    private \M2E\Temu\Model\UnmanagedProduct\Mapping\MagentoProductFinder $magentoProductFinder;
    private \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType;

    public function __construct(
        \M2E\Temu\Helper\Magento\Product $magentoProductHelper,
        MagentoProductFinder $magentoProductFinder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
    ) {
        $this->magentoProductHelper = $magentoProductHelper;
        $this->magentoProductFinder = $magentoProductFinder;
        $this->configurableType = $configurableType;
    }

    public function findMagentoProduct(
        \M2E\Temu\Model\UnmanagedProduct $unmanaged
    ): ?\Magento\Catalog\Model\Product {
        $mappingTypes = $unmanaged->getAccount()->getUnmanagedListingSettings()->getMappingTypesByPriority();
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

        $product = null;

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
        $settings = $unmanaged->getAccount()->getUnmanagedListingSettings();

        if ($settings->isMappingBySkuModeByProductId()) {
            foreach ($unmanaged->getVariants() as $variant) {
                $childProduct = $this->magentoProductFinder
                    ->findProductByProductId(trim($variant->getSku()));

                if ($childProduct === null) {
                    continue;
                }

                $parentProduct = $this->findParentProduct($childProduct);
                if ($parentProduct !== null) {
                    return $parentProduct;
                }
            }

            return null;
        }

        if ($settings->isMappingBySkuModeBySku()) {
            foreach ($unmanaged->getVariants() as $variant) {
                $childProduct = $this->magentoProductFinder->findProductByAttribute(
                    $unmanaged->getRelatedStoreId(),
                    'sku',
                    trim($variant->getSku())
                );

                if ($childProduct === null) {
                    continue;
                }

                $parentProduct = $this->findParentProduct($childProduct);
                if ($parentProduct !== null) {
                    return $parentProduct;
                }
            }

            return null;
        }

        if ($settings->isMappingBySkuModeByAttribute()) {
            foreach ($unmanaged->getVariants() as $variant) {
                $childProduct = $this->magentoProductFinder->findProductByAttribute(
                    $unmanaged->getRelatedStoreId(),
                    $settings->getMappingAttributeBySku() ?: '',
                    trim($variant->getSku())
                );

                if ($childProduct === null) {
                    continue;
                }

                $parentProduct = $this->findParentProduct($childProduct);
                if ($parentProduct !== null) {
                    return $parentProduct;
                }
            }

            return null;
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
            $product = $this->magentoProductFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                'name',
                $unmanagedProductTitle
            );

            if ($this->isMagentoProductTypeAllowed($product->getTypeId())) {
                return $product;
            }

            return null;
        }

        if ($settings->isMappingByTitleModeByAttribute()) {
            $product = $this->magentoProductFinder->findProductByAttribute(
                $unmanaged->getRelatedStoreId(),
                $settings->getMappingAttributeByTitle(),
                $unmanagedProductTitle
            );

            if ($this->isMagentoProductTypeAllowed($product->getTypeId())) {
                return $product;
            }

            return null;
        }

        return null;
    }

    private function isMagentoProductTypeAllowed($type): bool
    {
        $allowedTypes = [
            ProductModel::TYPE_CONFIGURABLE_ORIGIN,
        ];

        return in_array($type, $allowedTypes);
    }

    private function isProductTypeCompatible(
        \Magento\Catalog\Model\Product $magentoProduct
    ): bool {
        if ($this->magentoProductHelper->isConfigurableType($magentoProduct->getTypeId())) {
            return true;
        }

        return false;
    }

    private function findParentProduct(\Magento\Catalog\Model\Product $childProduct): ?\Magento\Catalog\Model\Product
    {
        $parentIds = $this->configurableType->getParentIdsByChild($childProduct->getId());
        if (empty($parentIds)) {
            return null;
        }

        $firstParentId = array_shift($parentIds);
        $parentProduct = $this->magentoProductFinder->findProductByProductId($firstParentId);

        if ($parentProduct !== null) {
            return $parentProduct;
        }

        return null;
    }
}
