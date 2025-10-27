<?php

declare(strict_types=1);

namespace M2E\Temu\Model\InventorySync\Processing;

class ResultHandler implements \M2E\Temu\Model\Processing\PartialResultHandlerInterface
{
    public const NICK = 'inventory_sync';

    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private \M2E\Temu\Model\Account $account;
    private \M2E\Temu\Model\UnmanagedProduct\UpdateFromChannelFactory $unmanagedProductUpdateFromChannelProcessorFactory;
    private \M2E\Temu\Model\InventorySync\ProductBuilderFactory $channelProductCollectionBuilderFactory;
    private \M2E\Temu\Model\Product\UpdateFromChannel $productUpdateFromChannelProcessor;
    private \DateTime $fromDate;
    private \M2E\Temu\Model\InventorySync\ReceivedProduct\Processor $receivedProductProcessor;

    public function __construct(
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\UnmanagedProduct\UpdateFromChannelFactory $unmanagedProductUpdateFromChannelProcessorFactory,
        \M2E\Temu\Model\InventorySync\ProductBuilderFactory $channelProductCollectionBuilderFactory,
        \M2E\Temu\Model\Product\UpdateFromChannel $productUpdateFromChannelProcessor,
        \M2E\Temu\Model\InventorySync\ReceivedProduct\Processor $receivedProductProcessor
    ) {
        $this->accountRepository = $accountRepository;
        $this->unmanagedProductUpdateFromChannelProcessorFactory = $unmanagedProductUpdateFromChannelProcessorFactory;
        $this->channelProductCollectionBuilderFactory = $channelProductCollectionBuilderFactory;
        $this->productUpdateFromChannelProcessor = $productUpdateFromChannelProcessor;
        $this->receivedProductProcessor = $receivedProductProcessor;
    }

    public function initialize(array $params): void
    {
        if (!isset($params['account_id'])) {
            throw new \M2E\Temu\Model\Exception\Logic('Processing params is not valid.');
        }

        $account = $this->accountRepository->find($params['account_id']);
        if ($account === null) {
            throw new \M2E\Temu\Model\Exception('Account not found');
        }

        $this->account = $account;

        if (isset($params['current_date'])) {
            $this->fromDate = \M2E\Core\Helper\Date::createDateGmt($params['current_date']);
        }
    }

    public function processPartialResult(array $partialData): void
    {
        $channelProductBuilder = $this->channelProductCollectionBuilderFactory->create($this->account);
        $channelProductCollection = $channelProductBuilder->build($partialData);

        $this->receivedProductProcessor->collectReceivedProducts(
            $channelProductCollection,
            $this->account
        );

        $existedInListingProductCollection = $this->unmanagedProductUpdateFromChannelProcessorFactory
            ->create($this->account)
            ->process(clone $channelProductCollection);

        if (!$existedInListingProductCollection->empty()) {
            $this->productUpdateFromChannelProcessor
                ->process($existedInListingProductCollection, $this->account);
        }
    }

    public function processSuccess(array $resultData, array $messages): void
    {
        $this->account->setInventoryLastSyncDate(clone $this->fromDate);
        $this->accountRepository->save($this->account);

        $this->receivedProductProcessor->processDeletedProducts(clone $this->fromDate, $this->account);
    }

    public function processExpire(): void
    {
        // do nothing
    }

    public function clearLock(\M2E\Temu\Model\Processing\LockManager $lockManager): void
    {
        $lockManager->delete(\M2E\Temu\Model\Account::LOCK_NICK, $this->account->getId());
    }
}
