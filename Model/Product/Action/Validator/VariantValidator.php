<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class VariantValidator
{
    protected const VARIATION_COUNT_MAXIMUM = 100;

    private \M2E\Temu\Model\Product\Action\Validator\VariantSku\PriceValidator $priceValidator;
    private \M2E\Temu\Model\Product\Action\Validator\VariantSku\QtyValidator $qtyValidator;
    private \M2E\Temu\Model\Product\Action\Validator\VariantSku\SameSkuAlreadyExists $sameSkuAlreadyExists;
    private \M2E\Temu\Model\Product\Action\Validator\VariantSku\IdentifierValidator $identifierValidator;

    public function __construct(
        \M2E\Temu\Model\Product\Action\Validator\VariantSku\PriceValidator $priceValidator,
        \M2E\Temu\Model\Product\Action\Validator\VariantSku\QtyValidator $qtyValidator,
        \M2E\Temu\Model\Product\Action\Validator\VariantSku\SameSkuAlreadyExists $sameSkuAlreadyExists,
        \M2E\Temu\Model\Product\Action\Validator\VariantSku\IdentifierValidator $identifierValidator
    ) {
        $this->priceValidator = $priceValidator;
        $this->qtyValidator = $qtyValidator;
        $this->sameSkuAlreadyExists = $sameSkuAlreadyExists;
        $this->identifierValidator = $identifierValidator;
    }

    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): array {
        $variants = $product->getVariants();
        $messages = [];

        if (count($variants) > self::VARIATION_COUNT_MAXIMUM) {
            $messages[] = new ValidatorMessage(
                sprintf(
                    'The number of product variations cannot exceed %s.',
                    self::VARIATION_COUNT_MAXIMUM
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_VARIATIONS_EXCEED_LIMIT
            );

            return $messages;
        }

        $totalVariantsQty = 0;
        foreach ($variants as $variant) {
            $totalVariantsQty += $variant->getDataProvider()->getQty()->getValue();
        }

        if ($totalVariantsQty <= 0) {
            $messages[] = (string)__('The Product Quantity must be greater than 0.');

            return $messages;
        }

        foreach ($variants as $variant) {
            $variantHasError = false;

            if ($error = $this->priceValidator->validate($variant)) {
                $messages[] = $error;
                $variantHasError = true;
            }

            if ($error = $this->qtyValidator->validate($variant)) {
                $messages[] = $error;
                $variantHasError = true;
            }

            if (
                $variantSettings->isAddAction($variant->getId())
            ) {
                if ($error = $this->sameSkuAlreadyExists->validate($variant)) {
                    $messages[] = $error;
                    $variantHasError = true;
                }

                if ($error = $this->identifierValidator->validate($variant)) {
                    $messages[] = $error;
                    $variantHasError = true;
                }
            }

            if ($variantHasError) {
                return $messages;
            }
        }

        return [];
    }
}
