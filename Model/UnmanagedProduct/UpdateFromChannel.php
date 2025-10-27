<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct;

use M2E\Temu\Model\Channel\Product\ProductCollection as ChannelProductCollection;

class UpdateFromChannel
{
    private Repository $unmanagedRepository;
    private \M2E\Temu\Model\Account $account;
    private \M2E\Temu\Model\UnmanagedProduct\MappingService $mappingService;
    private \M2E\Temu\Model\Product\Repository $listingProductRepository;
    private \M2E\Temu\Model\UnmanagedProduct\DeleteService $unmanagedDeleteService;
    private \M2E\Temu\Model\UnmanagedProduct\CreateService $unmanagedCreateService;

    public function __construct(
        \M2E\Temu\Model\Account $account,
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository,
        \M2E\Temu\Model\Product\Repository $listingProductRepository,
        \M2E\Temu\Model\UnmanagedProduct\MappingService $mappingService,
        \M2E\Temu\Model\UnmanagedProduct\CreateService $unmanagedCreateService,
        \M2E\Temu\Model\UnmanagedProduct\DeleteService $unmanagedDeleteService
    ) {
        $this->account = $account;
        $this->unmanagedRepository = $unmanagedRepository;
        $this->mappingService = $mappingService;
        $this->listingProductRepository = $listingProductRepository;
        $this->unmanagedCreateService = $unmanagedCreateService;
        $this->unmanagedDeleteService = $unmanagedDeleteService;
    }

    public function process(ChannelProductCollection $channelProductCollection): ChannelProductCollection
    {
        if ($channelProductCollection->empty()) {
            return new ChannelProductCollection();
        }

        $existInListingProductCollection = $this->removeExistInListingProduct($channelProductCollection);

        $this->processExist($channelProductCollection);
        $unmanagedItems = $this->processNew($channelProductCollection);

        // remove not exist

        $this->autoMapping($unmanagedItems);

        return $existInListingProductCollection;
    }

    private function removeExistInListingProduct(
        ChannelProductCollection $channelProductCollection
    ): ChannelProductCollection {
        $existInProductCollection = new ChannelProductCollection();
        if ($channelProductCollection->empty()) {
            return $existInProductCollection;
        }

        $existed = $this->listingProductRepository->findByChannelIds(
            $channelProductCollection->getProductsChannelIds(),
            $this->account->getId()
        );

        /**
         * @var \M2E\Temu\Model\Channel\Product $product
         */
        foreach ($existed as $product) {
            if (!$channelProductCollection->has($product->getChannelProductId())) { // fix for duplicate products
                continue;
            }

            $existInProductCollection->add($channelProductCollection->get($product->getChannelProductId()));

            $channelProductCollection->remove($product->getChannelProductId());
        }

        return $existInProductCollection;
    }

    private function processExist(ChannelProductCollection $channelProductCollection): void
    {
        if ($channelProductCollection->empty()) {
            return;
        }

        $existProducts = $this->unmanagedRepository->findByChannelIds(
            $channelProductCollection->getProductsChannelIds(),
            $this->account->getId()
        );

        foreach ($existProducts as $existProduct) {
            if (!$channelProductCollection->has($existProduct->getChannelProductId())) {
                continue;
            }

            $channelProduct = $channelProductCollection->get($existProduct->getChannelProductId());

            $channelProductCollection->remove($existProduct->getChannelProductId());

            $existProduct->updateFromChannel($channelProduct);

            $this->unmanagedRepository->save($existProduct);
        }
    }

    /**
     * @param \M2E\Temu\Model\Channel\Product\ProductCollection $channelProductCollection
     *
     * @return \M2E\Temu\Model\UnmanagedProduct[]
     */
    private function processNew(ChannelProductCollection $channelProductCollection): array
    {
        $result = [];
        foreach ($channelProductCollection->getAll() as $item) {
            $unmanaged = $this->unmanagedCreateService->create($item);

            $result[] = $unmanaged;
        }

        return $result;
    }

    /**
     * @param \M2E\Temu\Model\UnmanagedProduct[] $unmanagedListings
     */
    private function autoMapping(array $unmanagedListings): void
    {
        $this->mappingService->autoMapUnmanagedProducts($unmanagedListings);
    }
}
