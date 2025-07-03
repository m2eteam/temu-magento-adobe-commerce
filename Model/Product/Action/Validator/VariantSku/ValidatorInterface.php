<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator\VariantSku;

interface ValidatorInterface
{
    public function validate(\M2E\Temu\Model\Product\VariantSku $variant): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage;
}
