<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Order\Shipment;

class TrackingDetailsBuilder
{
    private \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory;
    private \M2E\Core\Helper\Magento\Carriers $carriersHelper;

    public function __construct(
        \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory,
        \M2E\Core\Helper\Magento\Carriers $carriersHelper
    ) {
        $this->carrierFactory = $carrierFactory;
        $this->carriersHelper = $carriersHelper;
    }

    public function build(
        \Magento\Sales\Model\Order\Shipment $shipment,
        int $storeId
    ): ?\M2E\Temu\Model\Order\Shipment\Data\TrackingDetails {
        $track = $this->getLastTrack($shipment);
        if ($track === null) {
            return null;
        }

        $trackNumber = $this->getTrackNumber($track);
        if (empty($trackNumber)) {
            return null;
        }

        $carrierCode = $this->getTrackCarrierCode($track);

        return new \M2E\Temu\Model\Order\Shipment\Data\TrackingDetails(
            (int)$shipment->getId(),
            $carrierCode,
            $this->getTrackCarrierTitle($track, $storeId),
            $this->getTrackTitle($track),
            $trackNumber,
            $this->isCustomCarrierCode($carrierCode)
        );
    }

    private function getLastTrack(
        \Magento\Sales\Model\Order\Shipment $shipment
    ): ?\Magento\Sales\Model\Order\Shipment\Track {
        $tracks = $shipment->getTracks();
        if (empty($tracks)) {
            $tracks = $shipment->getTracksCollection()->getItems();
        }

        if (empty($tracks)) {
            return null;
        }

        return end($tracks);
    }

    private function getTrackNumber(\Magento\Sales\Model\Order\Shipment\Track $track): string
    {
        $trackNumber = $track->getNumber();
        return trim((string)$trackNumber);
    }

    private function getTrackCarrierCode(\Magento\Sales\Model\Order\Shipment\Track $track): string
    {
        return trim((string)$track->getCarrierCode());
    }

    private function getTrackCarrierTitle(
        \Magento\Sales\Model\Order\Shipment\Track $track,
        int $storeId
    ): string {
        $carrierCode = $this->getTrackCarrierCode($track);
        $carrier = $this->carrierFactory->create($carrierCode, $storeId);

        if ($carrier) {
            return trim((string)$carrier->getConfigData('title'));
        }

        return $carrierCode;
    }

    private function getTrackTitle(\Magento\Sales\Model\Order\Shipment\Track $track): string
    {
        return trim((string)$track->getTitle());
    }

    private function isCustomCarrierCode(string $carrierCode): bool
    {
        if ($carrierCode === \M2E\Temu\Model\Magento\Order\Shipment\Track::CUSTOM_CARRIER_CODE) {
            return true;
        }

        foreach ($this->carriersHelper->getCarriersWithAvailableTracking() as $carrier) {
            if ($carrier->getCarrierCode() === $carrierCode) {
                return false;
            }
        }

        return true;
    }
}
