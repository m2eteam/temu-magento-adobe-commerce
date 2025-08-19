<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator\VariantSku;

class VariationAttributesValidator implements ValidatorInterface
{
    public function validate(\M2E\Temu\Model\Product\VariantSku $variant): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage
    {
        $variationAttributes = $variant->getDataProvider()->getSalesAttributesData()->getValue();

        if (count($variationAttributes) === 0) {
            return new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                (string)__(
                    'Temu variation attribute is missing a value. Please ensure at least one valid value is provided.'
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_VARIATION_ATTRIBUTE_MISSING
            );
        }

        return null;
    }
}
