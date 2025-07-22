<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class ProductVariantsTotalValidator implements ValidatorInterface
{
    protected const VARIATION_COUNT_MAXIMUM = 100;

    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $configurator
    ): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage {
        $variants = $product->getVariants();

        if (count($variants) > self::VARIATION_COUNT_MAXIMUM) {
            return new ValidatorMessage(
                sprintf(
                    'The number of product variations cannot exceed %s.',
                    self::VARIATION_COUNT_MAXIMUM
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_VARIATIONS_EXCEED_LIMIT
            );
        }

        $totalVariantsQty = 0;
        foreach ($variants as $variant) {
            $totalVariantsQty += $variant->getDataProvider()->getQty()->getValue();
        }

        if ($totalVariantsQty <= 0) {
            return new ValidatorMessage(
                'The Product Quantity must be greater than 0.',
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_ZERO_QTY
            );
        }

        return null;
    }
}
