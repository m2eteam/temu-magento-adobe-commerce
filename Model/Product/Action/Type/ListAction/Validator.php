<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\ListAction;

class Validator extends \M2E\Temu\Model\Product\Action\Type\AbstractValidator
{
    private \M2E\Temu\Model\Product\Action\Validator\VariantValidator $variantValidator;
    /** @var \M2E\Temu\Model\Product\Action\Validator\ValidatorInterface[] */
    private array $validators;

    public function __construct(
        \M2E\Temu\Model\Product\Action\Validator\VariantValidator $variantValidator,
        array $validators = []
    ) {
        $this->variantValidator = $variantValidator;
        $this->validators = $validators;
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

        $variantErrors = $this->variantValidator->validate($product, $variantSettings);
        foreach ($variantErrors as $variantError) {
            $this->addMessage($variantError);
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
