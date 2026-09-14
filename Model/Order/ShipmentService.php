<?php

namespace M2E\Temu\Model\Order;

class ShipmentService
{
    public const HANDLE_RESULT_FAILED = -1;
    public const HANDLE_RESULT_SKIPPED = 0;
    public const HANDLE_RESULT_SUCCEEDED = 1;

    private \M2E\Temu\Model\Order\Shipment\TrackingDetailsBuilder $trackingDetailsBuilder;
    private \M2E\Temu\Model\Order\Shipment\ItemLoader $itemLoader;
    private \M2E\Temu\Model\Order\Change\Repository $orderChangeRepository;
    private \M2E\Temu\Model\Order\ChangeCreateService $orderChangeCreateService;
    private \M2E\Temu\Model\Order\Item\Repository $orderItemRepository;
    private \M2E\Temu\Model\Account\Ui\UrlHelper $urlHelper;
    private \M2E\Temu\Model\ShippingProvider\Repository $shippingProviderRepository;

    public function __construct(
        \M2E\Temu\Model\Order\Item\Repository $orderItemRepository,
        \M2E\Temu\Model\Order\Shipment\TrackingDetailsBuilder $trackingDetailsBuilder,
        \M2E\Temu\Model\Order\Shipment\ItemLoader $itemLoader,
        \M2E\Temu\Model\Order\Change\Repository $orderChangeRepository,
        \M2E\Temu\Model\Order\ChangeCreateService $orderChangeCreateService,
        \M2E\Temu\Model\Account\Ui\UrlHelper $urlHelper,
        \M2E\Temu\Model\ShippingProvider\Repository $shippingProviderRepository
    ) {
        $this->trackingDetailsBuilder = $trackingDetailsBuilder;
        $this->itemLoader = $itemLoader;
        $this->orderChangeRepository = $orderChangeRepository;
        $this->orderChangeCreateService = $orderChangeCreateService;
        $this->orderItemRepository = $orderItemRepository;
        $this->urlHelper = $urlHelper;
        $this->shippingProviderRepository = $shippingProviderRepository;
    }

    public function shipByShipment(
        \M2E\Temu\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment,
        int $initiator
    ): int {
        $order->getLogService()->setInitiator($initiator);

        if (!$order->canUpdateShippingStatus()) {
            $order->addErrorLog(
                strtr(
                    "Shipping details could not be sent to the Channel. " .
                    "Reason: Order status on channel_title is already marked as 'Shipped'.",
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                )
            );

            return self::HANDLE_RESULT_SKIPPED;
        }

        $trackingDetails = $this->trackingDetailsBuilder->build($shipment, $order->getStoreId());
        if ($trackingDetails === null) {
            $order->addErrorLog(
                "Shipping details could not be sent to the Channel. " .
                "Reason: Magento Shipping doesn't have Tracking number."
            );

            return self::HANDLE_RESULT_FAILED;
        }

        $existOrderChange = $this->findExistOrderChange($order, $trackingDetails);
        $orderItemsToShip = $this->itemLoader->loadItemsByShipment($order, $shipment, $existOrderChange);
        if (empty($orderItemsToShip)) {
            $order->addErrorLog(
                "Shipping details could not be sent to the Channel. " .
                "Reason: The order Items have either already been shipped or are not included in this order."
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        $shippingProviderMapping = $order->getAccount()->getShippingProviderMapping();
        if (!$shippingProviderMapping->isConfigured()) {
            $order->addErrorLog(
                'Missing <a href="%url%" target="_blank">Shipping Carrier Mapping</a>. ' .
                'Please ensure the shipping carrier mapping is correctly configured to synchronize ' .
                'order shipping data with %channel_title%',
                [
                    '!url' => $this->urlHelper->getEditUrl($order->getAccountId(), [
                        'tab' => 'invoices_and_shipments',
                    ]),
                    '!channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                ]
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        $shippingProviderId = $this->findShippingProviderId(
            $shippingProviderMapping,
            $trackingDetails,
            $order
        );
        if ($shippingProviderId === null) {
            $order->addErrorLog(
                strtr(
                    'Failed to map Magento Shipping Carrier to channel_title Shipping Carrier.',
                    [
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                )
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        $orderChange = $this->createOrderChange(
            $order,
            $orderItemsToShip,
            $trackingDetails,
            $shippingProviderId,
            $initiator,
            $existOrderChange
        );

        $this->writeTrackingNumberAddedLog($order, $trackingDetails);

        return self::HANDLE_RESULT_SUCCEEDED;
    }

    private function findExistOrderChange(
        \M2E\Temu\Model\Order $order,
        \M2E\Temu\Model\Order\Shipment\Data\TrackingDetails $trackingDetails
    ): ?\M2E\Temu\Model\Order\Change {
        $existChanges = $this->orderChangeRepository->findShippingNotStarted((int)$order->getId());
        foreach ($existChanges as $existChange) {
            $changeParams = $existChange->getParams();

            if (!isset($changeParams['magento_shipment_id'])) {
                continue;
            }

            if ($changeParams['magento_shipment_id'] !== $trackingDetails->getMagentoShipmentId()) {
                continue;
            }

            return $existChange;
        }

        return null;
    }

    private function findShippingProviderId(
        \M2E\Temu\Model\Account\ShippingMapping $shippingProviderMapping,
        \M2E\Temu\Model\Order\Shipment\Data\TrackingDetails $trackingDetails,
        \M2E\Temu\Model\Order $order
    ): ?int {
        $shippingProviderId = $shippingProviderMapping->getProviderIdByCarrierCodeAndRegionId(
            $order->getRegionId(),
            $trackingDetails->getCarrierCode()
        );

        if (
            $shippingProviderId === null
            && $trackingDetails->isCustomCarrier()
            && $order->getAccount()->getInvoiceAndShipmentSettings()->isMapShippingProviderByCustomCarrierTitle()
        ) {
            $shippingProvider = $this->shippingProviderRepository->findByAccountRegionAndTitle(
                $order->getAccountId(),
                $order->getRegionId(),
                $trackingDetails->getCarrierName()
            );

            if ($shippingProvider !== null) {
                $shippingProviderId = $shippingProvider->getShippingProviderId();
            }
        }

        if ($shippingProviderId === null) {
            $shippingProviderId = $shippingProviderMapping->getDefaultProviderId($order->getRegionId());
        }

        return $shippingProviderId;
    }

    /**
     * @param \M2E\Temu\Model\Order\Item[] $itemsToShip
     */
    private function createOrderChange(
        \M2E\Temu\Model\Order $order,
        array $itemsToShip,
        \M2E\Temu\Model\Order\Shipment\Data\TrackingDetails $trackingDetails,
        int $shippingProviderId,
        int $initiator,
        ?\M2E\Temu\Model\Order\Change $existOrderChange
    ): \M2E\Temu\Model\Order\Change {
        $params = [
            'magento_shipment_id' => $trackingDetails->getMagentoShipmentId(),
            'tracking_number' => $trackingDetails->getTrackingNumber(),
            'shipping_provider_id' => $shippingProviderId,
            'items' => array_map(static function ($item) {
                return [
                    'item_id' => $item->getId(),
                ];
            }, $itemsToShip),
        ];

        if ($existOrderChange !== null) {
            $existOrderChange->setParams($params);

            $this->orderChangeRepository->save($existOrderChange);

            return $existOrderChange;
        }

        return $this->orderChangeCreateService->create(
            (int)$order->getId(),
            \M2E\Temu\Model\Order\Change::ACTION_UPDATE_SHIPPING,
            $initiator,
            $params,
        );
    }

    private function writeTrackingNumberAddedLog(
        \M2E\Temu\Model\Order $order,
        Shipment\Data\TrackingDetails $trackingDetails
    ): void {
        $order->addInfoLog(
            'Tracking number "%tracking_number%" for "%carrier_name%" was added to the Shipment.',
            [
                '!tracking_number' => $trackingDetails->getTrackingNumber(),
                '!carrier_name' => $trackingDetails->getCarrierName(),
            ]
        );
    }

    private function removeExistOrderChange(\M2E\Temu\Model\Order $order, ?Change $existOrderChange): void
    {
        if ($existOrderChange === null) {
            return;
        }

        foreach ($existOrderChange->getOrderItemsIdsForShipping() as $id) {
            $item = $order->getItem($id);
            $item->setShippingInProgressNo();

            $this->orderItemRepository->save($item);
        }

        $this->orderChangeRepository->delete($existOrderChange);
    }
}
