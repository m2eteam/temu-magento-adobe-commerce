<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Connector\Order\Get;

use M2E\Temu\Model\Channel\Connector\Order\Get\Items\Response;

trait CommandTrait
{
    public function getCommand(): array
    {
        return ['order', 'get', 'items'];
    }

    public function parseResponse(
        \M2E\Core\Model\Connector\Response $response
    ): Items\Response {
        $responseData = $response->getResponseData();

        if (!array_key_exists('orders', $responseData)) {
            throw new \M2E\Temu\Model\Exception('Server don`t return "orders" array');
        }

        $orders = [];
        foreach ($responseData['orders'] as $order) {
            /** @var \M2E\Temu\Model\Channel\Order\Item[] $orderItem */
            $orderItem = [];
            foreach ($order['items'] as $item) {
                $shipment = null;
                if (isset($item['shipment'])) {
                    $shipment = new \M2E\Temu\Model\Channel\Order\Item\Shipment(
                        (int)$item['shipment']['qty'],
                        $item['shipment']['supplier_name'],
                        $item['shipment']['tracking_number'],
                    );
                }

                $orderItem[] = new \M2E\Temu\Model\Channel\Order\Item(
                    $item['id'],
                    (string)$item['goods_id'],
                    $item['sku'] ?? null,
                    (string)$item['sku_id'],
                    $item['status'],
                    (int)$item['qty'],
                    (int)$item['qty_cancelled_before_shipment'],
                    (int)$item['fulfillment_type'],
                    new \M2E\Temu\Model\Channel\Order\Item\Price(
                        (float)$item['price']['unit_retail'],
                        (float)$item['price']['unit_base'],
                    ),
                    $shipment
                );
            }

            $deliveryAddress = null;
            if (isset($order['delivery_address'])) {
                $deliveryAddress =  new \M2E\Temu\Model\Channel\Order\DeliveryAddress(
                    $order['delivery_address']['name'],
                    $order['delivery_address']['address_line_all'],
                    $order['delivery_address']['line_1'],
                    $order['delivery_address']['line_2'],
                    $order['delivery_address']['line_3'],
                    $order['delivery_address']['country'],
                    $order['delivery_address']['city'],
                    $order['delivery_address']['postcode'],
                    $order['delivery_address']['country_code'],
                    $order['delivery_address']['state'] ?? null
                );
            }

            $buyer = null;
            if (isset($order['buyer'])) {
                $buyer = new \M2E\Temu\Model\Channel\Order\Buyer(
                    $order['buyer']['name'],
                    $order['buyer']['email'],
                    $order['buyer']['phone'] ?? null,
                );
            }

            $orders[] = new \M2E\Temu\Model\Channel\Order(
                $order['id'],
                (int)$order['site_id'],
                (int)$order['region_id'],
                $order['status'],
                $order['currency_code'],
                new \M2E\Temu\Model\Channel\Order\Tax(
                    (float)$order['tax']['total'],
                    (float)$order['tax']['after_discount'],
                    (float)$order['tax']['product_amount'],
                    (float)$order['tax']['shipping_amount']
                ),
                new \M2E\Temu\Model\Channel\Order\Price(
                    (float)$order['price']['total'],
                    (float)$order['price']['delivery'],
                    (float)$order['price']['discount'],
                ),
                $buyer,
                $deliveryAddress,
                \M2E\Core\Helper\Date::tryCreateImmutableDateGmt($order['ship_by_date']),
                \M2E\Core\Helper\Date::tryCreateImmutableDateGmt($order['shipping_time']),
                \M2E\Core\Helper\Date::tryCreateImmutableDateGmt($order['deliver_by_date']),
                \M2E\Core\Helper\Date::createImmutableDateGmt($order['create_date']),
                \M2E\Core\Helper\Date::createImmutableDateGmt($order['update_date']),
                $orderItem
            );
        }

        $toDate = \M2E\Core\Helper\Date::createImmutableDateGmt(
            $responseData['to_update_date'] ?? $responseData['to_create_date'],
        );

        return new Response(
            $orders,
            $toDate,
            $response->getMessageCollection()
        );
    }
}
