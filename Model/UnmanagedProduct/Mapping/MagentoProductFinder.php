<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\Mapping;

class MagentoProductFinder
{
    private \Magento\Catalog\Model\ProductFactory $productFactory;

    public function __construct(\Magento\Catalog\Model\ProductFactory $productFactory)
    {
        $this->productFactory = $productFactory;
    }

    public function findProductByProductId(
        string $productId
    ): ?\Magento\Catalog\Model\Product {
        if (
            empty($productId)
            || !\ctype_digit($productId)
            || (int)$productId <= 0
        ) {
            return null;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create();
        $product = $product->load($productId);

        if ($product->getId()) {
            return $product;
        }

        return null;
    }

    public function findProductByAttribute(
        int $storeId,
        string $attributeCode,
        string $attributeValue
    ): ?\Magento\Catalog\Model\Product {
        if (
            empty($attributeCode)
            || empty($attributeValue)
        ) {
            return null;
        }

        /** @var \Magento\Catalog\Model\Product $productObj */
        $productObj = $this->productFactory->create()->setStoreId($storeId);
        $productObj = $productObj->loadByAttribute($attributeCode, $attributeValue);

        if (
            $productObj instanceof \Magento\Catalog\Model\Product
            && $productObj->getId()
        ) {
            return $productObj;
        }

        return null;
    }
}
