<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Relist;

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
        if (!$this->getListingProduct()->isRelistable()) {
            $this->addMessage(
                new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                    'The Item either is Listed, or not Listed yet or not available',
                    \M2E\Temu\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                )
            );

            return false;
        }

        foreach ($this->validators as $validator) {
            $error = $validator->validate($product, $actionConfigurator);
            if ($error !== null) {
                $this->addMessage($error);
            }
        }

        $variants = $product->getVariants();
        foreach ($variants as $variant) {
            foreach ($this->variantValidators as $variantValidator) {
                $error = $variantValidator->validate($variant);
                if ($error !== null) {
                    $this->addMessage($error);
                }
            }
        }

        return !$this->hasErrorMessages();
    }
}
