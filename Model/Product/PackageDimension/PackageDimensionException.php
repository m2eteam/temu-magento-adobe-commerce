<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\PackageDimension;

class PackageDimensionException extends \M2E\Temu\Model\Exception\Logic
{
    public const CODE_WEIGHT_NOT_CONFIGURED = 1;
    public const CODE_LENGTH_NOT_CONFIGURED = 2;
    public const CODE_WIDTH_NOT_CONFIGURED  = 3;
    public const CODE_HEIGHT_NOT_CONFIGURED = 4;
    public const CODE_DIMENSIONS_MISSING = 5;
    public const CODE_WEIGHT_MISSING = 6;
}
