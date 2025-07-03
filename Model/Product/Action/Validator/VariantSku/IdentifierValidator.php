<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator\VariantSku;

class IdentifierValidator implements ValidatorInterface
{
    public function validate(\M2E\Temu\Model\Product\VariantSku $variant): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage
    {
        if (empty($variant->getDataProvider()->getIdentifier()->getValue())) {
            return new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                (string)__('EAN is missing a value'),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_EAN_MISSING
            );
        }

        return null;
    }
}
