<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\Dto;

class VariationDataItem
{
    private string $attributeCode;
    private string $attributeName;
    private string $value;

    public function __construct(string $attributeCode, string $attributeName, string $value)
    {
        //if (empty($attributeCode)) {
        //    throw new \M2E\Temu\Model\Exception\Logic('Attribute code must not be empty');
        //}
        //
        //if (empty($attributeName)) {
        //    throw new \M2E\Temu\Model\Exception\Logic('Attribute name must not be empty');
        //}
        //
        //if (empty($value)) {
        //    throw new \M2E\Temu\Model\Exception\Logic('Attribute value must not be empty');
        //}

        $this->attributeCode = $attributeCode;
        $this->attributeName = $attributeName;
        $this->value = $value;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
