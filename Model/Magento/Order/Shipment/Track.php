<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Magento\Order\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;

class Track
{
    public const CUSTOM_CARRIER_CODE = 'custom';

    private \Magento\Sales\Model\Order $magentoOrder;
    private array $trackingDetails;
    private \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory;
    private \M2E\Temu\Observer\Shipment\EventRuntimeManager $shipmentEventRuntimeManager;

    public function __construct(
        \Magento\Sales\Model\Order $magentoOrder,
        array $trackingDetails,
        \M2E\Temu\Observer\Shipment\EventRuntimeManager $shipmentEventRuntimeManager,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory
    ) {
        $this->magentoOrder = $magentoOrder;
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->shipmentEventRuntimeManager = $shipmentEventRuntimeManager;
        $this->trackingDetails = $trackingDetails;
    }

    public function create(): array
    {
        $trackingDetails = $this->getFilteredTrackingDetails();
        if (empty($trackingDetails)) {
            return [];
        }

        // Skip shipment observer
        // ---------------------------------------
        $this->shipmentEventRuntimeManager->skipEvents();
        // ---------------------------------------

        /** @var \Magento\Sales\Model\Order\Shipment[] $shipments */
        $shipments = $this->magentoOrder->getShipmentsCollection()->getItems();

        if (empty($shipments)) {
            return [];
        }

        $tracks = [];
        foreach ($trackingDetails as $trackingDetail) {
            /** @var \M2E\Temu\Model\Order\Item $orderItem */
            foreach ($trackingDetail['order_items'] as $orderItem) {
                $trackNumber = (string)$trackingDetail['tracking_number'];
                $shipment = $this->findShipment($shipments, $trackNumber, $orderItem);

                if ($shipment === null) {
                    return [];
                }

                // Sometimes Magento returns an array instead of Collection by a call of $shipment->getTracksCollection()
                if (
                    $shipment->hasData(ShipmentInterface::TRACKS)
                    && !($shipment->getData(ShipmentInterface::TRACKS) instanceof TrackCollection)
                ) {
                    $shipment->unsetData(ShipmentInterface::TRACKS);
                }

                $track = $this->shipmentTrackFactory->create();
                $track->setNumber($trackingDetail['tracking_number']);
                $track->setTitle($trackingDetail['supplier_name']);
                $track->setCarrierCode($trackingDetail['supplier_name']);

                $shipment->addTrack($track)->save();

                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    private function getFilteredTrackingDetails(): array
    {
        if (empty($this->magentoOrder->getTracksCollection()->getSize())) {
            return $this->trackingDetails;
        }

        foreach ($this->magentoOrder->getTracksCollection() as $track) {
            foreach ($this->trackingDetails as $key => $trackingDetail) {
                if (
                    strtolower((string)$track->getData('track_number')) === strtolower(
                        (string)$trackingDetail['tracking_number']
                    )
                ) {
                    unset($this->trackingDetails[$key]);
                }
            }
        }

        return $this->trackingDetails;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment[] $shipments
     */
    private function findShipment(
        array $shipments,
        string $trackNumber,
        \M2E\Temu\Model\Order\Item $orderItem
    ): ?\Magento\Sales\Model\Order\Shipment {
        $shipmentsNew = [];
        foreach ($shipments as $shipment) {
            foreach ($shipment->getItems() as $shipmentItem) {
                if ((int)$shipmentItem->getProductId() === $orderItem->getMagentoProductId()) {
                    $shipmentsNew[] = $shipment;
                }
            }
        }

        $shipmentWithoutTracks = [];
        foreach ($shipmentsNew as $shipment) {
            if ($this->isTrackNumberExistInShipment($trackNumber, $shipment)) {
                continue;
            }

            $shipmentWithoutTracks[] = $shipment;
        }

        if (empty($shipmentWithoutTracks)) {
            return null;
        }

        return $shipmentWithoutTracks[0];
    }

    private function isTrackNumberExistInShipment(
        string $trackNumber,
        \Magento\Sales\Model\Order\Shipment $shipment
    ): bool {
        $trackNumber = $this->clearTrackNumber($trackNumber);

        foreach ($shipment->getTracks() as $track) {
            $shippingTrackNumber = $this->clearTrackNumber($track->getTrackNumber());
            if ($shippingTrackNumber === $trackNumber) {
                return true;
            }
        }

        return false;
    }

    private function clearTrackNumber(string $trackNumber): string
    {
        return str_replace(['/', ' ', '-'], '', $trackNumber);
    }
}
