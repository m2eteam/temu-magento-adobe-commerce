<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider\BulletPoints;

class Value
{
    public array $bulletPoints;
    public ?string $hash;

    public function __construct(
        array $bulletPoints,
        ?string $hash
    ) {
        $this->bulletPoints = $bulletPoints;
        $this->hash = $hash;
    }
}
