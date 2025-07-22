<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\DataProvider\Images;

class Value
{
    /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Image[] */
    public array $set;

    public function __construct(
        array $set
    ) {
        $this->set = $set;
    }
}
