<?php

namespace M2E\Temu\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command;

use Magento\InventoryApi\Api\Data\SourceItemInterface;

class Save extends \M2E\Temu\Plugin\AbstractPlugin
{
    private \M2E\Temu\Model\MSI\AffectedProducts $msiAffectedProducts;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \M2E\Temu\Model\Magento\Product\ChangeAttributeTrackerFactory $changeAttributeTrackerFactory;
    private \M2E\Temu\Model\Listing\LogService $listingLogService;

    private \Magento\Inventory\Model\SourceItemRepository $sourceItemRepo;

    public function __construct(
        \M2E\Temu\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \M2E\Temu\Model\Magento\Product\ChangeAttributeTrackerFactory $changeAttributeTrackerFactory,
        \M2E\Temu\Model\Listing\LogService $listingLogService,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->changeAttributeTrackerFactory = $changeAttributeTrackerFactory;
        $this->listingLogService = $listingLogService;

        $this->sourceItemRepo = $objectManager->get(\Magento\Inventory\Model\SourceItemRepository::class);
    }

    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    protected function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems */
        $sourceItems = $arguments[0];
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItemsBefore */
        $sourceItemsBefore = [];

        foreach ($sourceItems as $sourceItem) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceItem->getSourceCode())
                ->addFilter(SourceItemInterface::SKU, $sourceItem->getSku())
                ->create();

            foreach ($this->sourceItemRepo->getList($searchCriteria)->getItems() as $beforeSourceItem) {
                $sourceItemsBefore[(string)$sourceItem->getSourceCode()] = $beforeSourceItem;
            }
        }

        $result = $callback(...$arguments);

        foreach ($sourceItems as $sourceItem) {
            $sourceItemBefore = $sourceItemsBefore[$sourceItem->getSourceCode()] ?? null;

            $affectedProductCollection = $this->msiAffectedProducts->getAffectedProductsBySourceAndSku(
                $sourceItem->getSourceCode(),
                $sourceItem->getSku()
            );

            if ($affectedProductCollection->isEmpty()) {
                continue;
            }

            $this->addListingProductInstructions($affectedProductCollection);

            $this->processQty($sourceItemBefore, $sourceItem, $affectedProductCollection);
            $this->processStockAvailability($sourceItemBefore, $sourceItem, $affectedProductCollection);
        }

        return $result;
    }

    private function processQty(
        ?\Magento\InventoryApi\Api\Data\SourceItemInterface $beforeSourceItem,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem,
        \M2E\Temu\Model\Product\AffectedProduct\Collection $affectedProductCollection
    ) {
        $newValue = $afterSourceItem->getQuantity();
        $oldValue = 'undefined';
        if ($beforeSourceItem !== null) {
            $oldValue = $beforeSourceItem->getQuantity();
        }

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($affectedProductCollection->getProducts() as $affectedProduct) {
            $this->logListingProductMessage(
                $affectedProduct,
                $afterSourceItem,
                \M2E\Temu\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                $oldValue,
                $newValue
            );
        }
    }

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface|null $beforeSourceItem
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem
     * @param \M2E\Temu\Model\Product\AffectedProduct\Collection $affectedProductCollection
     */
    private function processStockAvailability(
        ?\Magento\InventoryApi\Api\Data\SourceItemInterface $beforeSourceItem,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem,
        \M2E\Temu\Model\Product\AffectedProduct\Collection $affectedProductCollection
    ) {
        $newValue = $afterSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';
        $oldValue = 'undefined';
        if ($beforeSourceItem !== null) {
            $oldValue = $beforeSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';
        }

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($affectedProductCollection->getProducts() as $affectedProduct) {
            $this->logListingProductMessage(
                $affectedProduct,
                $afterSourceItem,
                \M2E\Temu\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY,
                $oldValue,
                $newValue
            );
        }
    }

    private function logListingProductMessage(
        \M2E\Temu\Model\Product\AffectedProduct\Product $affectedProduct,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem,
        $action,
        $oldValue,
        $newValue
    ): void {
        $description = \M2E\Temu\Helper\Module\Log::encodeDescription(
            'Value was changed from [%from%] to [%to%] in the "%source%" Source.',
            ['!from' => $oldValue, '!to' => $newValue, '!source' => $sourceItem->getSourceCode()]
        );

        $this->listingLogService->addProduct(
            $affectedProduct->getProduct(),
            \M2E\Core\Helper\Data::INITIATOR_EXTENSION,
            $action,
            null,
            $description,
            \M2E\Temu\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    private function addListingProductInstructions(
        \M2E\Temu\Model\Product\AffectedProduct\Collection $affectedProductCollection
    ): void {
        foreach ($affectedProductCollection->getProducts() as $affectedProduct) {
            $changeAttributeTracker = $this->changeAttributeTrackerFactory->create(
                $affectedProduct->getProduct()
            );
            $changeAttributeTracker->addInstructionWithPotentiallyChangedType();
            $changeAttributeTracker->flushInstructions();
        }
    }
}
