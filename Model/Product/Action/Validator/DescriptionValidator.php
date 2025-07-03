<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class DescriptionValidator implements ValidatorInterface
{
    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $configurator
    ): ?ValidatorMessage {
        if (!$configurator->isDescriptionAllowed()) {
            return null;
        }

        $description = $product->getDataProvider()->getDescription()->getValue()->description;

        if (empty($description)) {
            return new ValidatorMessage(
                'Product Description is missing',
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_DESCRIPTION_MISSING
            );
        }

        return null;
    }
}
