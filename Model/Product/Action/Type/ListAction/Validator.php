<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\ListAction;

class Validator extends \M2E\Temu\Model\Product\Action\Type\AbstractValidator
{
    /** @var \M2E\Temu\Model\Product\Action\Validator\ValidatorInterface[] */
    private array $validators;
    /** @var \M2E\Temu\Model\Product\Action\Validator\VariantSku\ValidatorInterface[] */
    private array $variantValidators;

    public function __construct(
        array $validators = [],
        array $variantValidators = []
    ) {
        $this->validators = $validators;
        $this->variantValidators = $variantValidators;
    }

    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): bool {
        if (!$product->isListable()) {
            $this->addMessage(
                new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                    'Item is Listed or not available',
                    \M2E\Temu\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                )
            );

            return false;
        }

        $existErrors = [];

        foreach ($product->getVariants() as $variant) {
            foreach ($this->variantValidators as $variantValidator) {
                $error = $variantValidator->validate($variant);
                if ($error === null) {
                    continue;
                }

                $errorText = $error->getText();

                if (!isset($existErrors[$errorText])) {
                    $existErrors[$errorText] = true;
                    $this->addMessage($error);
                }
            }
        }

        foreach ($this->validators as $validator) {
            $error = $validator->validate($product, $actionConfigurator);
            if ($error !== null) {
                $this->addMessage($error);
            }
        }

        return !$this->hasErrorMessages();
    }
}
