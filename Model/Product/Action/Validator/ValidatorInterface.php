<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

interface ValidatorInterface
{
    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $configurator
    ): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage;
}
