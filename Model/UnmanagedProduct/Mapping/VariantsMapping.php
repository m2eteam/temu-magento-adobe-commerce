<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\Mapping;

class VariantsMapping
{
    private \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository;

    public function __construct(
        \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory,
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository
    ) {
        $this->magentoProductFactory = $magentoProductFactory;
        $this->unmanagedRepository = $unmanagedRepository;
    }

    public function mapSimpleVariants(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ): void {
        $magentoProductId = (int)$magentoProduct->getId();
        $unmanagedProduct->mapToMagentoProduct($magentoProductId);
        $this->unmanagedRepository->save($unmanagedProduct);

        $variant = $unmanagedProduct->getFirstVariant();
        $variant->mapToMagentoProduct($magentoProductId);
        $this->unmanagedRepository->saveVariant($variant);
    }

    public function mapConfigurableVariants(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ): void {
        $unmanagedListingSettings = $unmanagedProduct->getAccount()->getUnmanagedListingSettings();

        foreach ($unmanagedListingSettings->getMappingTypesByPriority() as $type) {
            if ($type === \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_TITLE) {
                continue;
            }

            if ($type === \M2E\Temu\Model\Account\Settings\UnmanagedListings::MAPPING_TYPE_BY_SKU) {
                if ($unmanagedListingSettings->isMappingBySkuModeBySku()) {
                    $this->mapBySku($unmanagedProduct, $magentoProduct);
                }

                if ($unmanagedListingSettings->isMappingBySkuModeByProductId()) {
                    $this->mapByProductId($unmanagedProduct, $magentoProduct);
                }

                if ($unmanagedListingSettings->isMappingBySkuModeByAttribute()) {
                    $this->mapByAttribute(
                        $unmanagedListingSettings->getMappingAttributeBySku() ?: '',
                        $unmanagedProduct,
                        $magentoProduct
                    );
                }
            }
        }
    }

    private function mapBySku(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ): void {
        $magentoVariantsBySku = $this->getMagentoProductVariantsBySku($magentoProduct);
        foreach ($unmanagedProduct->getVariants() as $variant) {
            $unmanagedVariantSku = $variant->getSku();
            if (empty($unmanagedVariantSku)) {
                continue;
            }

            if (!isset($magentoVariantsBySku[$unmanagedVariantSku])) {
                continue;
            }

            $variant->mapToMagentoProduct((int)$magentoVariantsBySku[$unmanagedVariantSku]->getId());
            $this->unmanagedRepository->saveVariant($variant);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $magentoProduct
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getMagentoProductVariantsBySku(\Magento\Catalog\Model\Product $magentoProduct): array
    {
        $magentoVariants = $magentoProduct->getTypeInstance()->getUsedProducts($magentoProduct);
        $result = [];
        /** @var \Magento\Catalog\Api\Data\ProductInterface $magentoVariant */
        foreach ($magentoVariants as $magentoVariant) {
            $result[$magentoVariant->getSku()] = $magentoVariant;
        }

        return $result;
    }

    private function mapByProductId(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ) {
        $magentoVariantsByProductId = $this->getMagentoProductVariantsByProductId($magentoProduct);
        foreach ($unmanagedProduct->getVariants() as $variant) {
            $unmanagedVariantSku = $variant->getSku();
            if (empty($unmanagedVariantSku)) {
                continue;
            }

            if (!isset($magentoVariantsByProductId[$unmanagedVariantSku])) {
                continue;
            }

            $variant->mapToMagentoProduct((int)$magentoVariantsByProductId[$unmanagedVariantSku]->getId());
            $this->unmanagedRepository->saveVariant($variant);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $magentoProduct
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getMagentoProductVariantsByProductId(\Magento\Catalog\Model\Product $magentoProduct): array
    {
        $magentoVariants = $magentoProduct->getTypeInstance()->getUsedProducts($magentoProduct);
        $result = [];
        /** @var \Magento\Catalog\Api\Data\ProductInterface $magentoVariant */
        foreach ($magentoVariants as $magentoVariant) {
            $result[$magentoVariant->getId()] = $magentoVariant;
        }

        return $result;
    }

    private function mapByAttribute(
        string $attributeCode,
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \Magento\Catalog\Model\Product $magentoProduct
    ) {
        $magentoVariantsByProductId = $this
            ->getMagentoProductVariantsByAttribute($magentoProduct, $attributeCode);
        foreach ($unmanagedProduct->getVariants() as $variant) {
            $unmanagedVariantSku = $variant->getSku();
            if (empty($unmanagedVariantSku)) {
                continue;
            }

            if (!isset($magentoVariantsByProductId[$unmanagedVariantSku])) {
                continue;
            }

            $variant->mapToMagentoProduct($magentoVariantsByProductId[$unmanagedVariantSku]->getId());
            $this->unmanagedRepository->saveVariant($variant);
        }
    }

    private function getMagentoProductVariantsByAttribute(
        \Magento\Catalog\Model\Product $magentoProduct,
        $attributeCode
    ): array {
        $magentoVariants = $magentoProduct->getTypeInstance()->getUsedProducts($magentoProduct);
        $result = [];
        /** @var \Magento\Catalog\Api\Data\ProductInterface $magentoVariant */
        foreach ($magentoVariants as $magentoVariant) {
            $attributeValue = $this->magentoProductFactory
                ->createByProductId((int)$magentoVariant->getId())
                ->getAttributeValue($attributeCode);
            $result[$attributeValue] = $magentoVariant;
        }

        return $result;
    }
}
