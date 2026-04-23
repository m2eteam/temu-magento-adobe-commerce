<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\DataProvider;

use M2E\Temu\Model\Product\DataProvider\DataBuilderHelpTrait;
use M2E\Temu\Model\Product\DataProvider\DataBuilderInterface;
use M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\Item;

class SalesAttributesProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'SalesAttributes';

    private \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\CategoryProcessor $categoryAttributeProcessor;
    private \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\AttributeDataProcessor $attributeDataProcessor;

    public function __construct(
        \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\CategoryProcessor $categoryAttributeProcessor,
        \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\AttributeDataProcessor $attributeDataProcessor
    ) {
        $this->categoryAttributeProcessor = $categoryAttributeProcessor;
        $this->attributeDataProcessor = $attributeDataProcessor;
    }

    public function getSalesAttributesData(\M2E\Temu\Model\Product\VariantSku $variantSku, bool $asSimple): array
    {
        if (
            $asSimple
            || $variantSku->getProduct()->isSimple()
        ) {
            return $this->getSalesAttributesDataByCategoryAttributes($variantSku);
        }

        return $this->getSalesAttributesDataByMagentoConfigurableAttributes($variantSku);
    }

    private function getSalesAttributesDataByCategoryAttributes(
        \M2E\Temu\Model\Product\VariantSku $variantSku
    ): array {
        $result = array_map(static function (Item $attribute) {
            return [
                'parent_spec_id' => $attribute->getParentSpecId(),
                'spec_id' => $attribute->getSpecId(),
                'value' => $attribute->getValue(),
                'value_id' => $attribute->getValueId(),
                'name' => $attribute->getName(),
            ];
        }, $this->categoryAttributeProcessor->getAttributes($variantSku));
        $this->collectWarningMessages($this->categoryAttributeProcessor->getWarningMessages());

        return $result;
    }

    private function getSalesAttributesDataByMagentoConfigurableAttributes(
        \M2E\Temu\Model\Product\VariantSku $variantSku
    ): array {
        $result = array_map(static function (Item $attribute) {
            return [
                'parent_spec_id' => $attribute->getParentSpecId(),
                'spec_id' => $attribute->getSpecId(),
                'value' => $attribute->getValue(),
                'value_id' => $attribute->getValueId(),
                'name' => $attribute->getName(),
            ];
        }, $this->attributeDataProcessor->getAttributes($variantSku));
        $this->collectWarningMessages($this->attributeDataProcessor->getWarningMessages());

        return $result;
    }

    private function collectWarningMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addWarningMessage($message);
        }
    }
}
