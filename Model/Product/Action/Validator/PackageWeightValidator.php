<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class PackageWeightValidator implements ValidatorInterface
{
    use PackageDimensionExceptionHandlerTrait;

    private \M2E\Temu\Model\Product\PackageDimensionFinder $packageDimensionFinder;

    public function __construct(
        \M2E\Temu\Model\Product\PackageDimensionFinder $packageDimensionFinder
    ) {
        $this->packageDimensionFinder = $packageDimensionFinder;
    }

    public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $configurator
    ): ?ValidatorMessage {
        try {
            $weight = $this->packageDimensionFinder->getWeight($product);
            $value = $weight->getValue();

            if ($value <= 0) {
                return new ValidatorMessage(
                    (string)__(
                        'The product package weight is missing or invalid.
                To list the Product, please make sure that the Package settings are correct.'
                    ),
                    \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_PACKAGE_WEIGHT_MISSING_OR_INVALID
                );
            }
        } catch (\M2E\Temu\Model\Product\PackageDimension\PackageDimensionException $exception) {
            return $this->createValidatorMessageFromException($exception);
        }

        return null;
    }
}
