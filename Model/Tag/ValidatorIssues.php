<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Tag;

class ValidatorIssues
{
    public const NOT_USER_ERROR = 'not-user-error';

    public const ERROR_CATEGORY_SETTINGS_NOT_SET = '0001-m2e';
    public const ERROR_DESCRIPTION_MISSING = '0002-m2e';
    public const ERROR_IMAGES_MISSING = '0003-m2e';
    public const ERROR_IMAGES_INVALID = '0004-m2e';
    public const ERROR_PACKAGE_SIZE_OUT_OF_RANGE = '0005-m2e';
    public const ERROR_PACKAGE_WEIGHT_NOT_SET = '0006-m2e';
    public const ERROR_PACKAGE_LENGTH_NOT_SET = '0007-m2e';
    public const ERROR_PACKAGE_WIDTH_NOT_SET = '0008-m2e';
    public const ERROR_PACKAGE_HEIGHT_NOT_SET = '0009-m2e';
    public const ERROR_PACKAGE_WEIGHT_MISSING_OR_INVALID = '0010-m2e';
    public const ERROR_PRODUCT_NAME_INVALID_LENGTH = '0011-m2e';
    public const ERROR_VARIATIONS_EXCEED_LIMIT = '0012-m2e';
    public const ERROR_EAN_MISSING = '0013-m2e';
    public const ERROR_ZERO_PRICE = '0014-m2e';
    public const ERROR_QUANTITY_POLICY_CONTRADICTION = '0015-m2e';
    public const ERROR_QTY_EXCEEDS_MAXIMUM = '0016-m2e';
    public const ERROR_DUPLICATE_SKU_UNMANAGED = '0017-m2e';
    public const ERROR_DUPLICATE_SKU_LISTING = '0018-m2e';
    public const ERROR_PACKAGE_DIMENSIONS_MISSING = '0019-m2e';
    public const ERROR_PACKAGE_WEIGHT_MISSING = '0020-m2e';
    public const ERROR_ZERO_QTY = '0021-m2e';
    public const ERROR_VARIATION_ATTRIBUTE_MISSING = '0022-m2e';
    public const ERROR_CATEGORY_ATTRIBUTE_MISSING = '0023-m2e';

    public function mapByCode(string $code): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage
    {
        $map = [
            self::ERROR_CATEGORY_SETTINGS_NOT_SET => (string)__('Category Settings are not set.'),
            self::ERROR_DESCRIPTION_MISSING => (string)__('Product Description is missing.'),
            self::ERROR_IMAGES_MISSING => (string)__('Product Images are missing.'),
            self::ERROR_IMAGES_INVALID => (string)__('Product Images are invalid.'),
            self::ERROR_PACKAGE_SIZE_OUT_OF_RANGE => (string)__('The product package size must be within the allowed size range.'),
            self::ERROR_PACKAGE_WEIGHT_NOT_SET => (string)__('Package Weight not configured.'),
            self::ERROR_PACKAGE_LENGTH_NOT_SET => (string)__('Package Length not configured.'),
            self::ERROR_PACKAGE_WIDTH_NOT_SET => (string)__('Package Width not configured.'),
            self::ERROR_PACKAGE_HEIGHT_NOT_SET => (string)__('Package Height not configured.'),
            self::ERROR_PACKAGE_WEIGHT_MISSING_OR_INVALID => (string)__('The product package weight is missing or invalid.'),
            self::ERROR_PRODUCT_NAME_INVALID_LENGTH => (string)__('The product name must contain between 1 and 255 characters.'),
            self::ERROR_VARIATIONS_EXCEED_LIMIT => (string)__('The number of product variations exceeds the allowed limit.'),
            self::ERROR_EAN_MISSING => (string)__('EAN is missing a value.'),
            self::ERROR_ZERO_PRICE => (string)__('The Product Price cannot be 0.'),
            self::ERROR_QUANTITY_POLICY_CONTRADICTION => (string)__('You\'re submitting an item with QTY contradicting the QTY settings in your Selling Policy.'),
            self::ERROR_QTY_EXCEEDS_MAXIMUM => (string)__('Product QTY exceeds the allowed limit.'),
            self::ERROR_DUPLICATE_SKU_UNMANAGED => (string)__('Product with the same SKU already exists in Unmanaged Items.'),
            self::ERROR_DUPLICATE_SKU_LISTING => (string)__('Product with the same SKU already exists in another Listing.'),
            self::ERROR_PACKAGE_DIMENSIONS_MISSING => (string)__('Package Dimensions are missing.'),
            self::ERROR_PACKAGE_WEIGHT_MISSING => (string)__('Package Weight is missing.'),
            self::ERROR_ZERO_QTY => (string)__('The Product Quantity must be greater than 0.'),
            self::ERROR_VARIATION_ATTRIBUTE_MISSING => (string)__('Temu variation attribute is missing a value. Please ensure at least one valid value is provided.'),
            self::ERROR_CATEGORY_ATTRIBUTE_MISSING => (string)__('Unable to List Product Due to missing Item Attribute(s)'),
        ];

        if (!isset($map[$code])) {
            return null;
        }

        return new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
            $map[$code],
            $code
        );
    }
}
