<?php

namespace M2E\Temu\Model\Order\Tax;

interface PriceTaxRateInterface
{
    public function getValue(): float;

    public function getNotRoundedValue(): float;

    public function isEnabledRoundingOfValue(): bool;
}
