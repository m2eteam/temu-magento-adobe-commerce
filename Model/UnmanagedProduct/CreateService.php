<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct;

class CreateService
{
    private \M2E\Temu\Model\UnmanagedProductFactory $productFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $repository;
    private \M2E\Temu\Model\UnmanagedProduct\VariantSkuFactory $variantSkuFactory;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProductFactory $productFactory,
        \M2E\Temu\Model\UnmanagedProduct\Repository $repository,
        \M2E\Temu\Model\UnmanagedProduct\VariantSkuFactory $variantSkuFactory
    ) {
        $this->productFactory = $productFactory;
        $this->repository = $repository;
        $this->variantSkuFactory = $variantSkuFactory;
    }

    public function create(
        \M2E\Temu\Model\Channel\Product $channelProduct
    ): \M2E\Temu\Model\UnmanagedProduct {
        $unmanagedProduct = $this->productFactory->createFromChannel($channelProduct);
        $this->repository->create($unmanagedProduct);

        $this->createVariants($unmanagedProduct, $channelProduct->getVariantSkusCollection());

        $unmanagedProduct->calculateDataByVariants();
        $this->repository->save($unmanagedProduct);

        return $unmanagedProduct;
    }

    private function createVariants(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \M2E\Temu\Model\Channel\Product\VariantSku\VariantSkuCollection $channelVariantSkuCollection
    ): void {
        $variants = [];

        foreach ($channelVariantSkuCollection->getAll() as $productSku) {
            $variants[] = $this->createVariantEntity($unmanagedProduct, $productSku);
        }

        $this->repository->saveVariants($variants);
    }

    private function createVariantEntity(
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        \M2E\Temu\Model\Channel\Product\VariantSku $variantSku
    ): \M2E\Temu\Model\UnmanagedProduct\VariantSku {
        return $this->variantSkuFactory->createFromChannel(
            $variantSku,
            $unmanagedProduct
        );
    }
}
