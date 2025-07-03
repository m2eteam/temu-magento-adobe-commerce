<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class CategoryValidator implements ValidatorInterface
{
    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $configurator
    ): ?ValidatorMessage {
        if (!$configurator->isCategoriesAllowed()) {
            return null;
        }

        if (!$product->hasCategoryTemplate()) {
            return new ValidatorMessage(
                'Categories Settings are not set',
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_CATEGORY_SETTINGS_NOT_SET
            );
        }

        return null;
    }
}
