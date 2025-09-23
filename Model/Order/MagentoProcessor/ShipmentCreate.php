<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Order\MagentoProcessor;

class ShipmentCreate
{
    private \M2E\Temu\Helper\Module\Exception $helperModuleException;
    private \M2E\Temu\Model\Magento\Order\ShipmentFactory $shipmentFactory;
    private \M2E\Temu\Model\Order\Repository $orderRepository;

    public function __construct(
        \M2E\Temu\Model\Magento\Order\ShipmentFactory $shipmentFactory,
        \M2E\Temu\Helper\Module\Exception $helperModuleException,
        \M2E\Temu\Model\Order\Repository $orderRepository
    ) {
        $this->helperModuleException = $helperModuleException;
        $this->shipmentFactory = $shipmentFactory;
        $this->orderRepository = $orderRepository;
    }

    public function process(
        \M2E\Temu\Model\Order $order
    ): void {
        if (!$this->canCreateShipment($order)) {
            if (
                $order->getMagentoOrder()
                && $order->getMagentoOrder()->getIsVirtual()
            ) {
                $order->addInfoLog(
                    'Magento Order was created without the Shipping Address since your Virtual Product ' .
                    'has no weight and cannot be shipped.'
                );
            }

            return;
        }

        $itemsToShipment = $this->findItemsToShipment($order);
        if (empty($itemsToShipment)) {
            return;
        }

        try {
            $shipmentBuilder = $this->shipmentFactory->create(
                $order->getMagentoOrder(),
                $order,
                $itemsToShipment
            );
            $shipments = $shipmentBuilder->create();
        } catch (\Throwable $e) {
            $this->helperModuleException->process($e);
            $order->addErrorLog(
                'Shipment was not created. Reason: %msg%',
                ['msg' => $e->getMessage()]
            );

            return;
        }

        if (!empty($shipments)) {
            foreach ($shipments as $shipment) {
                $order->addSuccessLog('Shipment #%shipment_id% was created.', [
                    '!shipment_id' => $shipment->getIncrementId(),
                ]);

                $order->addCreatedMagentoShipment($shipment);
            }

            $this->orderRepository->save($order);
        }
    }

    private function canCreateShipment(\M2E\Temu\Model\Order $order): bool
    {
        if (
            !$order->isStatusShipping()
            && !$order->isStatusPartiallyShipped()
        ) {
            return false;
        }

        if (!$order->hasMagentoOrder()) {
            return false;
        }

        if (!$order->getAccount()->getInvoiceAndShipmentSettings()->isCreateMagentoShipment()) {
            return false;
        }

        $magentoOrder = $order->getMagentoOrder();
        if ($magentoOrder === null) {
            return false;
        }

        if ($magentoOrder->hasShipments() || !$magentoOrder->canShip()) {
            return false;
        }

        return true;
    }

    /**
     * @param \M2E\Temu\Model\Order $order
     *
     * @return \Magento\Sales\Model\Order\Item[]
     */
    private function findItemsToShipment(\M2E\Temu\Model\Order $order): array
    {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $order->getMagentoOrder();

        $orderItemsByProductId = [];
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->isStatusShipped()) {
                $orderItemsByProductId[$orderItem->getMagentoProductId()][] = $orderItem;
            }
        }

        $itemsToShip = [];
        foreach ($magentoOrder->getAllItems() as $magentoOrderItem) {
            if (empty($orderItemsByProductId[$magentoOrderItem->getProductId()])) {
                continue;
            }

            if (empty($magentoOrderItem->getQtyToShip())) {
                continue;
            }

            $itemsToShip[] = $magentoOrderItem;
        }

        return $itemsToShip;
    }
}
