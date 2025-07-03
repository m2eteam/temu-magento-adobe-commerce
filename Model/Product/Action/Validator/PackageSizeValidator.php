<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class PackageSizeValidator implements ValidatorInterface
{
    use PackageDimensionExceptionHandlerTrait;

    public const MAX_SIZE = 999.9;
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
            $size = $this->packageDimensionFinder->getSize($product);

            if (
                min($size->getLength(), $size->getWidth(), $size->getHeight()) <= 0
                || max($size->getLength(), $size->getWidth(), $size->getHeight()) > self::MAX_SIZE
            ) {
                return new ValidatorMessage(
                    sprintf(
                        'The product package size must be within %s %s.',
                        self::MAX_SIZE,
                        $size->getUnit()
                    ),
                    \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_PACKAGE_SIZE_OUT_OF_RANGE
                );
            }
        } catch (\M2E\Temu\Model\Product\PackageDimension\PackageDimensionException $exception) {
            return $this->createValidatorMessageFromException($exception);
        }

        return null;
    }
}
