<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\DataProvider\Images;

class Image
{
    public string $url;

    public function __construct(
        string $url
    ) {
        $this->url = $url;
    }
}
