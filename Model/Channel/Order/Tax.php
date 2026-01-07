<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Order;

class Tax
{
    public float $taxTotal;
    public float $taxAfterDiscount;
    public float $productTaxAmount;
    public float $shippingTaxAmount;

    public function __construct(
        float $taxTotal,
        float $taxAfterDiscount,
        float $productTaxAmount,
        float $shippingTaxAmount
    ) {
        $this->taxTotal = $taxTotal;
        $this->taxAfterDiscount = $taxAfterDiscount;
        $this->productTaxAmount = $productTaxAmount;
        $this->shippingTaxAmount = $shippingTaxAmount;
    }
}
