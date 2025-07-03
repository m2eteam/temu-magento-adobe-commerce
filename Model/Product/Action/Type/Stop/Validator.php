<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Stop;

class Validator extends \M2E\Temu\Model\Product\Action\Type\AbstractValidator
{
    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): bool {
        if (!$product->isStoppable()) {
            $this->addMessage(
                new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                    (string)__('Item is not Listed or not available'),
                    \M2E\Temu\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                )
            );

            return false;
        }

        return true;
    }
}
