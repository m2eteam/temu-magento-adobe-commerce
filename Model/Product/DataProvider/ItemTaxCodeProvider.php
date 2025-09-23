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
        $mode = $product->getSellingFormatTemplate()->getItemTaxCodeMode();

        if ($mode === \M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE) {
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

        if ($mode === \M2E\Temu\Model\Policy\SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE) {
            $customValue = $product->getSellingFormatTemplate()->getItemTaxCodeCustomValue();
            $this->onlineItemTaxCodeValue = $customValue;

            return $customValue;
        }

        return null;
    }

    public function getMetaData(): array
    {
        return [
            self::NICK => $this->onlineItemTaxCodeValue,
        ];
    }
}
