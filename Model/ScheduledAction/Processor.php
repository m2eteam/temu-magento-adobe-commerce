<?php

namespace M2E\Temu\Model\ScheduledAction;

use M2E\Temu\Model\Product\Action\Configurator;
use M2E\Temu\Model\ResourceModel\ScheduledAction\Collection as ScheduledActionCollection;

class Processor
{
    private const REVISE_VARIANTS_PRIORITY = 500;
    private const REVISE_DETAILS_PRIORITY = 50;
    private const LIST_PRIORITY = 25;
    private const RELIST_PRIORITY = 125;
    private const STOP_PRIORITY = 1000;

    private \M2E\Temu\Model\Config\Manager $config;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \M2E\Temu\Helper\Module\Exception $exceptionHelper;
    private \M2E\Temu\Model\Product\Action\Dispatcher $actionDispatcher;
    /** @var \M2E\Temu\Model\ScheduledAction\Repository */
    private Repository $scheduledActionRepository;

    public function __construct(
        \M2E\Temu\Model\ScheduledAction\Repository $scheduledActionRepository,
        \M2E\Temu\Model\Config\Manager $config,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \M2E\Temu\Helper\Module\Exception $exceptionHelper,
        \M2E\Temu\Model\Product\Action\Dispatcher $actionDispatcher
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
        $this->exceptionHelper = $exceptionHelper;
        $this->actionDispatcher = $actionDispatcher;
        $this->scheduledActionRepository = $scheduledActionRepository;
    }

    public function process(): void
    {
        $limit = $this->calculateActionsCountLimit();
        if ($limit === 0) {
            return;
        }

        $scheduledActions = $this->getScheduledActionsForProcessing($limit);
        if (empty($scheduledActions)) {
            return;
        }

        foreach ($scheduledActions as $scheduledAction) {
            try {
                $listingProduct = $scheduledAction->getListingProduct();
                $configurator = $scheduledAction->getConfigurator();
                $variantSettings = $scheduledAction->getVariantsSettings();
                $additionalData = $scheduledAction->getAdditionalData();
                $statusChanger = $scheduledAction->getStatusChanger();
            } catch (\M2E\Temu\Model\Exception\Logic $e) {
                $this->exceptionHelper->process($e);

                $this->scheduledActionRepository->remove($scheduledAction);

                continue;
            }

            $params = $additionalData['params'] ?? [];

            $listingProduct->setActionConfigurator($scheduledAction->getConfigurator());

            switch ($scheduledAction->getActionType()) {
                case \M2E\Temu\Model\Product::ACTION_LIST:
                    $this->actionDispatcher->processList($listingProduct, $configurator, $variantSettings, $params, $statusChanger);
                    break;
                case \M2E\Temu\Model\Product::ACTION_REVISE:
                    $this->actionDispatcher->processRevise($listingProduct, $configurator, $variantSettings, $params, $statusChanger);
                    break;
                case \M2E\Temu\Model\Product::ACTION_STOP:
                    $this->actionDispatcher->processStop($listingProduct, $configurator, $variantSettings, $params, $statusChanger);
                    break;
                case \M2E\Temu\Model\Product::ACTION_DELETE:
                    $this->actionDispatcher->processDelete($listingProduct, $configurator, $variantSettings, $params, $statusChanger);
                    break;
                case \M2E\Temu\Model\Product::ACTION_RELIST:
                    $this->actionDispatcher->processRelist($listingProduct, $configurator, $variantSettings, $params, $statusChanger);
                    break;
                default:
                    throw new \DomainException("Unknown action '{$scheduledAction->getActionType()}'");
            }

            $this->scheduledActionRepository->remove($scheduledAction);
        }
    }

    private function calculateActionsCountLimit(): int
    {
        $maxAllowedActionsCount = (int)$this->config->get(
            '/listing/product/scheduled_actions/',
            'max_prepared_actions_count'
        );

        if ($maxAllowedActionsCount <= 0) {
            return 0;
        }

        return $maxAllowedActionsCount;
    }

    /**
     * @return \M2E\Temu\Model\ScheduledAction[]
     */
    private function getScheduledActionsForProcessing(int $limit): array
    {
        $connection = $this->resourceConnection->getConnection();

        $unionSelect = $connection->select()->union([
            $this->getListScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseVariantsScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseTitleScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseDescriptionScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseImagesScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseCategoryAttributesScheduledActionsPreparedCollection()->getSelect(),
            $this->getReviseShippingScheduledActionsPreparedCollection()->getSelect(),
            $this->getRevisePriceScheduledActionsPreparedCollection()->getSelect(),
            $this->getRelistScheduledActionsPreparedCollection()->getSelect(),
            $this->getStopScheduledActionsPreparedCollection()->getSelect(),
            $this->getDeleteScheduledActionsPreparedCollection()->getSelect(),
        ]);

        $unionSelect->order(['coefficient DESC']);
        $unionSelect->order(['create_date ASC']);

        $unionSelect->distinct(true);
        $unionSelect->limit($limit);

        $scheduledActionsData = $unionSelect->query()->fetchAll();
        if (empty($scheduledActionsData)) {
            return [];
        }

        $scheduledActionsIds = [];
        foreach ($scheduledActionsData as $scheduledActionData) {
            $scheduledActionsIds[] = $scheduledActionData['id'];
        }

        return $this->scheduledActionRepository->getByIds($scheduledActionsIds);
    }

    private function getListScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::LIST_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_LIST
        );

        return $collection;
    }

    private function getReviseVariantsScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_VARIANTS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_VARIANTS);

        return $collection;
    }

    private function getReviseTitleScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_TITLE);

        return $collection;
    }

    private function getReviseDescriptionScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_DESCRIPTION);

        return $collection;
    }

    private function getReviseImagesScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_IMAGES);

        return $collection;
    }

    private function getReviseCategoryAttributesScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_CATEGORIES);

        return $collection;
    }

    private function getReviseShippingScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_SHIPPING);

        return $collection;
    }

    private function getRevisePriceScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::REVISE_DETAILS_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_REVISE
        );
        $collection->addTagFilter(Configurator::DATA_TYPE_PRICE);

        return $collection;
    }

    private function getRelistScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        $collection = $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::RELIST_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_RELIST
        );

        return $collection;
    }

    private function getStopScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        return $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::STOP_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_STOP
        );
    }

    private function getDeleteScheduledActionsPreparedCollection(): ScheduledActionCollection
    {
        return $this->scheduledActionRepository->createCollectionForFindByActionType(
            self::STOP_PRIORITY,
            \M2E\Temu\Model\Product::ACTION_DELETE
        );
    }
}
