<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider;

class ItemTaxCodeProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'ItemTaxCode';

    private ?string $onlineItemTaxCodeValue = null;

    public function getItemTaxCode(\M2E\Temu\Model\Product $product): ?string
    {
        $itemTaxCodeAttribute = $product->getSellingFormatTemplate()->getItemTaxCodeAttribute();
        if (empty($itemTaxCodeAttribute)) {
            return null;
        }

        $attributeValue = $product->getMagentoProduct()->getAttributeValue($itemTaxCodeAttribute);
        if (empty($attributeValue)) {
            return null;
        }

        $this->onlineItemTaxCodeValue = $attributeValue;

        return $attributeValue;
    }

    public function getMetaData(): array
    {
        return [
            self::NICK => $this->onlineItemTaxCodeValue,
        ];
    }
}
