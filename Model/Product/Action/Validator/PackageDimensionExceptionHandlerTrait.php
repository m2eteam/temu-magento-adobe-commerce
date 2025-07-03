<?php

namespace M2E\Temu\Model\Product\Action\Validator;

use M2E\Temu\Model\Product\PackageDimension\PackageDimensionException;
use M2E\Temu\Model\Tag\ValidatorIssues;

trait PackageDimensionExceptionHandlerTrait
{
    private function createValidatorMessageFromException(
        \M2E\Temu\Model\Product\PackageDimension\PackageDimensionException $exception
    ): ValidatorMessage {
        $code = ValidatorIssues::NOT_USER_ERROR;

        switch ($exception->getCode()) {
            case PackageDimensionException::CODE_WEIGHT_NOT_CONFIGURED:
                $code = ValidatorIssues::ERROR_PACKAGE_WEIGHT_NOT_SET;
                break;
            case PackageDimensionException::CODE_LENGTH_NOT_CONFIGURED:
                $code = ValidatorIssues::ERROR_PACKAGE_LENGTH_NOT_SET;
                break;
            case PackageDimensionException::CODE_WIDTH_NOT_CONFIGURED:
                $code = ValidatorIssues::ERROR_PACKAGE_WIDTH_NOT_SET;
                break;
            case PackageDimensionException::CODE_HEIGHT_NOT_CONFIGURED:
                $code = ValidatorIssues::ERROR_PACKAGE_HEIGHT_NOT_SET;
                break;
            case PackageDimensionException::CODE_DIMENSIONS_MISSING:
                $code = ValidatorIssues::ERROR_PACKAGE_DIMENSIONS_MISSING;
                break;
            case PackageDimensionException::CODE_WEIGHT_MISSING:
                $code = ValidatorIssues::ERROR_PACKAGE_WEIGHT_MISSING;
                break;
        }

        return new ValidatorMessage($exception->getMessage(), $code);
    }
}
