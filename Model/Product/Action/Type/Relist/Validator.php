<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Relist;

class Validator extends \M2E\Temu\Model\Product\Action\Type\AbstractValidator
{
    private \M2E\Temu\Model\Product\Action\Validator\VariantValidator $variantValidator;

    public function __construct(
        \M2E\Temu\Model\Product\Action\Validator\VariantValidator $variantValidator
    ) {
        $this->variantValidator = $variantValidator;
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

        $variantErrors = $this->variantValidator->validate($product, $variantSettings);
        foreach ($variantErrors as $variantError) {
            $this->addMessage($variantError);
        }

        return !$this->hasErrorMessages();
    }
}
