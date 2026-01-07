<?php

declare(strict_types=1);

namespace M2E\Temu\Model;

class OrderFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function createEmpty(): Order
    {
        return $this->objectManager->create(Order::class);
    }

    public function createFromChannel(Channel\Order $channelOrder, Account $account): Order
    {
        $obj = $this->createEmpty();
        $obj->create(
            $account,
            $channelOrder->getOrderId(),
            $channelOrder->getCreateDate(),
            $channelOrder->getCurrency()
        );

        $obj->setStatus(self::resolveStatus($channelOrder->getStatus()))
            // ----------------------------------------
            ->setSiteId($channelOrder->getSiteId())
            ->setRegionId($channelOrder->getRegionId())
            // ----------------------------------------
            ->setPriceTotal($channelOrder->getPrice()->total)
            ->setShippingPrice($channelOrder->getPrice()->delivery)
            ->setPriceDiscount($channelOrder->getPrice()->discount)
            // ----------------------------------------
            ->setShippingDetails(self::createShippingDetails($channelOrder))
            ->setTaxDetails(self::createTaxDetails($channelOrder))
            // ----------------------------------------
            ->setShipByDate($channelOrder->getShipByDate())
            ->setShippingTime($channelOrder->getShippingTime())
            ->setDeliverByDate($channelOrder->getDeliverByDate())
            ->setChannelUpdateDate($channelOrder->getUpdateDate())
        ;

        if ($channelOrder->getBuyer() !== null) {
            $obj->setBuyerName($channelOrder->getBuyer()->name)
            ->setBuyerEmail($channelOrder->getBuyer()->email)
            ->setBuyerPhone($channelOrder->getBuyer()->phone);
        }

        return $obj;
    }

    /**
     * @param \M2E\Temu\Model\Order $order
     * @param \M2E\Temu\Model\Channel\Order $channelOrder
     *
     * @return bool - was updated
     */
    public static function updateFromChannel(Order $order, Channel\Order $channelOrder): bool
    {
        $wasChanged = false;
        if ($order->getStatus() !== self::resolveStatus($channelOrder->getStatus())) {
            $order->setStatus(self::resolveStatus($channelOrder->getStatus()));

            $wasChanged = true;
        }

        if ($order->getPriceTotal() !== $channelOrder->getPrice()->total) {
            $order->setPriceTotal($channelOrder->getPrice()->total);

            $wasChanged = true;
        }

        if ($order->getShippingPrice() !== $channelOrder->getPrice()->delivery) {
            $order->setShippingPrice($channelOrder->getPrice()->delivery);

            $wasChanged = true;
        }

        if ($order->getPriceDiscount() !== $channelOrder->getPrice()->discount) {
            $order->setPriceDiscount($channelOrder->getPrice()->discount);

            $wasChanged = true;
        }

        if ($channelOrder->getBuyer() !== null) {
            if ($order->getBuyerName() !== $channelOrder->getBuyer()->name) {
                $order->setBuyerName($channelOrder->getBuyer()->name);

                $wasChanged = true;
            }

            if ($order->getBuyerEmail() !== $channelOrder->getBuyer()->email) {
                $order->setBuyerEmail($channelOrder->getBuyer()->email);

                $wasChanged = true;
            }

            if ($order->getBuyerPhone() !== $channelOrder->getBuyer()->phone) {
                $order->setBuyerPhone($channelOrder->getBuyer()->phone);

                $wasChanged = true;
            }
        }

        if ($channelOrder->getDeliveryAddress() !== null) {
            if ($order->getShippingDetails() !== self::createShippingDetails($channelOrder)) {
                $order->setShippingDetails(self::createShippingDetails($channelOrder));

                $wasChanged = true;
            }
        }

        if ($order->getTaxDetails() !== self::createTaxDetails($channelOrder)) {
            $order->setTaxDetails(self::createTaxDetails($channelOrder));

            $wasChanged = true;
        }

        if (
            $channelOrder->getShipByDate() !== null
            && $order->getShipByDate() != $channelOrder->getShipByDate()
        ) {
            $order->setShipByDate($channelOrder->getShipByDate());

            $wasChanged = true;
        }

        if (
            $channelOrder->getShippingTime() !== null
            && $order->getShippingTime() != $channelOrder->getShippingTime()
        ) {
            $order->setShippingTime($channelOrder->getShippingTime());

            $wasChanged = true;
        }

        if (
            $channelOrder->getDeliverByDate() !== null
            && $order->getDeliverByDate() != $channelOrder->getDeliverByDate()
        ) {
            $order->setDeliverByDate($channelOrder->getDeliverByDate());

            $wasChanged = true;
        }

        if ($order->getChannelUpdateDate() != $channelOrder->getUpdateDate()) {
            $order->setChannelUpdateDate($channelOrder->getUpdateDate());

            $wasChanged = true;
        }

        return $wasChanged;
    }

    // ----------------------------------------

    private static function resolveStatus(string $channelStatus): int
    {
        return \M2E\Temu\Model\Order\StatusResolver::resolve($channelStatus);
    }

    private static function createShippingDetails(Channel\Order $channelOrder): array
    {
        $deliveryAddress = $channelOrder->getDeliveryAddress();

        if ($deliveryAddress === null) {
            return [];
        }

        return [
            'address' => [
                'recipient_name' => $deliveryAddress->name,
                'street' => [
                    $deliveryAddress->line1,
                    $deliveryAddress->line2,
                    $deliveryAddress->line3,
                ],
                'postal_code' => $deliveryAddress->postCode,
                'country' => $deliveryAddress->country,
                'city' => $deliveryAddress->city,
                'country_code' => $deliveryAddress->countryCode,
                'state' => $deliveryAddress->state,
            ],
        ];
    }

    private static function createTaxDetails(Channel\Order $channelOrder): array
    {
        $taxDetails = $channelOrder->getTaxDetails();

        return [
            'amount' => $taxDetails->taxAfterDiscount,
            'taxTotal' => $taxDetails->taxTotal,
            'product_amount' => $taxDetails->productTaxAmount,
            'shipping_amount' => $taxDetails->shippingTaxAmount,
        ];
    }
}
