<?php

declare(strict_types=1);

namespace M2E\Temu\Model\InventorySync\ReceivedProduct;

class Processor
{
    private Repository $repository;
    private \M2E\Temu\Model\Product\Repository $productRepository;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository;
    private \M2E\Temu\Model\UnmanagedProduct\DeleteService $unmanagedProductDeleteService;
    private \M2E\Temu\Model\InstructionService $instructionService;
    private \M2E\Temu\Model\Listing\LogService $logService;
    private int $logActionId;
    private \M2E\Temu\Model\InventorySync\ReceivedProductFactory $receivedProductFactory;

    public function __construct(
        Repository $repository,
        \M2E\Temu\Model\InventorySync\ReceivedProductFactory $receivedProductFactory,
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository,
        \M2E\Temu\Model\UnmanagedProduct\DeleteService $unmanagedProductDeleteService,
        \M2E\Temu\Model\InstructionService $instructionService,
        \M2E\Temu\Model\Listing\LogService $logService
    ) {
        $this->repository = $repository;
        $this->productRepository = $productRepository;
        $this->unmanagedProductRepository = $unmanagedProductRepository;
        $this->unmanagedProductDeleteService = $unmanagedProductDeleteService;
        $this->instructionService = $instructionService;
        $this->logService = $logService;
        $this->receivedProductFactory = $receivedProductFactory;
    }

    public function clear(
        \M2E\Temu\Model\Account $account
    ): void {
        $this->repository->removeAllByAccount($account->getId());
    }

    public function collectReceivedProducts(
        \M2E\Temu\Model\Channel\Product\ProductCollection $productCollection,
        \M2E\Temu\Model\Account $account
    ): void {
        $receivedProducts = [];
        foreach ($productCollection->getAll() as $item) {
            $receivedProducts[] = $this->receivedProductFactory->create(
                $item->getChannelProductId(),
                $account->getId()
            );
        }

        $this->repository->createBatch($receivedProducts);
    }

    public function processDeletedProducts(
        \DateTime $inventorySyncProcessingStartDate,
        \M2E\Temu\Model\Account $account
    ): void {
        //$this->processNotReceivedProducts($account, $inventorySyncProcessingStartDate);
        $this->removeNotReceivedUnmanagedProducts($account);

        $this->repository
            ->removeAllByAccount($account->getId());
    }

    private function processNotReceivedProducts(
        \M2E\Temu\Model\Account $account,
        \DateTime $inventorySyncProcessingStartDate
    ): void {
        $removedProducts = $this->productRepository->findRemovedFromChannel(
            $inventorySyncProcessingStartDate,
            $account->getId(),
        );

        foreach ($removedProducts as $product) {
            $product->setStatusNotListed(\M2E\Temu\Model\Product::STATUS_CHANGER_COMPONENT);

            $this->productRepository->save($product);

            $this->logService->addRecordToProduct(
                \M2E\Temu\Model\Listing\Log\Record::createSuccess(
                    (string)__('Product was deleted and is no longer available on the channel'),
                ),
                $product,
                \M2E\Core\Helper\Data::INITIATOR_EXTENSION,
                \M2E\Temu\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                $this->getLogActionId(),
            );

            $this->instructionService->create(
                $product->getId(),
                \M2E\Temu\Model\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
                'channel_changes_synchronization',
                80,
            );
        }
    }

    private function removeNotReceivedUnmanagedProducts(
        \M2E\Temu\Model\Account $account
    ): void {
        $otherListings = $this->unmanagedProductRepository->findRemovedFromChannel(
            $account->getId()
        );

        foreach ($otherListings as $other) {
            $this->unmanagedProductDeleteService->process($other);
        }
    }

    private function getLogActionId(): int
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->logActionId ?? ($this->logActionId = $this->logService->getNextActionId());
    }
}
