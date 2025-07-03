<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class AttributeService
{
    private \M2E\Temu\Model\Channel\Attribute\Processor $attributeGetProcessor;
    private \M2E\Temu\Model\Connector\Brands\Get\Processor $brandGetProcessor;
    private \M2E\Temu\Model\Category\Dictionary\AttributeFactory $attributeFactory;

    public function __construct(
        \M2E\Temu\Model\Channel\Attribute\Processor $attributeGetProcessor,
        \M2E\Temu\Model\Connector\Brands\Get\Processor $brandGetProcessor,
        \M2E\Temu\Model\Category\Dictionary\AttributeFactory $attributeFactory
    ) {
        $this->attributeGetProcessor = $attributeGetProcessor;
        $this->brandGetProcessor = $brandGetProcessor;
        $this->attributeFactory = $attributeFactory;
    }

    public function getCategoryDataFromServer(
        string $region,
        int $categoryId,
        string $account
    ): \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response {
        return $this->attributeGetProcessor
            ->process($region, $categoryId, $account);
    }

    /*
        public function getBrandsDataFromServer( //TODO: brands
            \M2E\Temu\Model\Shop $shop,
            string $categoryId
        ): \M2E\Temu\Model\Connector\Brands\Get\Response {
            return $this->brandGetProcessor
                ->processAuthorizedBrands($shop->getAccount(), $shop, $categoryId);
        }
    */
    public function getProductAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): array {
        $productAttributes = [];
        foreach ($categoryData->getAttributes() as $responseAttribute) {
            if ($responseAttribute->isSalesType()) {
                continue;
            }

            $values = $this->getValues($responseAttribute);
            $productAttributes[] = $this->attributeFactory->createProductAttribute(
                $responseAttribute->getId(),
                $responseAttribute->getName(),
                $responseAttribute->isSale(),
                $responseAttribute->isRequired(),
                $responseAttribute->isCustomised(),
                $responseAttribute->isMultipleSelected(),
                $responseAttribute->getTypeFormat(),
                $responseAttribute->getRules(),
                $responseAttribute->getPid(),
                $responseAttribute->getRefPid(),
                $responseAttribute->getTemplatePid(),
                $responseAttribute->getParentSpecId(),
                $responseAttribute->getParentTemplatePid(),
                $values,
                $responseAttribute->getMultipleSelectedMaxCount()
            );
        }

        return $productAttributes;
    }

    public function getSalesAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): array {
        $salesAttributes = [];
        foreach ($categoryData->getAttributes() as $responseAttribute) {
            if (!$responseAttribute->isSalesType()) {
                continue;
            }
            $values = $this->getValues($responseAttribute);
            $salesAttributes[] = $this->attributeFactory->createSalesAttribute(
                $responseAttribute->getId(),
                $responseAttribute->getName(),
                $responseAttribute->isSale(),
                $responseAttribute->isRequired(),
                $responseAttribute->isCustomised(),
                $responseAttribute->isMultipleSelected(),
                $responseAttribute->getTypeFormat(),
                $responseAttribute->getRules(),
                $responseAttribute->getPid(),
                $responseAttribute->getRefPid(),
                $responseAttribute->getTemplatePid(),
                $responseAttribute->getParentSpecId(),
                $responseAttribute->getParentTemplatePid(),
                $values
            );
        }

        return $salesAttributes;
    }

    public function getTotalProductAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): int {
        $productAttributesCount = 0;

        foreach ($categoryData->getAttributes() as $attribute) {
            if ($attribute->isProductType() || $attribute->isSalesType()) {
                $productAttributesCount++;
            }
        }

        //$productAttributesCount++; // +1 for brand attribute

        $categoryRules = $categoryData->getRules();

        // + size chart attribute
        if ($categoryRules['size_chart']['is_supported'] ?? false) {
            ++$productAttributesCount;
        }

        return $productAttributesCount;
    }

    public function getTotalSalesAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): int {
        $salesAttributesCount = 0;

        foreach ($categoryData->getAttributes() as $attribute) {
            if ($attribute->isSalesType()) {
                $salesAttributesCount++;
            }
        }

        return $salesAttributesCount;
    }

    public function getHasRequiredAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): bool {
        foreach ($categoryData->getAttributes() as $attribute) {
            if ($attribute->isProductType() && $attribute->isRequired()) {
                return true;
            }
        }

        return false;
    }

    public function getHasRequiredSalesAttributes(
        \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response $categoryData
    ): bool {
        foreach ($categoryData->getAttributes() as $attribute) {
            if ($attribute->isSalesType() && $attribute->isRequired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \M2E\Temu\Model\Channel\Attribute\Item $responseAttribute
     *
     * @return \M2E\Temu\Model\Category\Dictionary\Attribute\Value[]
     */
    private function getValues(\M2E\Temu\Model\Channel\Attribute\Item $responseAttribute): array
    {
        $values = [];
        foreach ($responseAttribute->getValues() as $value) {
            $valueRelation = $this->getValueRelation($value);
            $values[] = $this->attributeFactory->createValue(
                $value['id'],
                $value['name'],
                $value['spec_id'],
                $value['group_id'],
                $valueRelation
            );
        }

        return $values;
    }

    private function getValueRelation(array $value): array
    {
        $result = [];
        if ($value['children_relation']) {
            foreach ($value['children_relation'] as $childRelation) {
                $result[] = $this->attributeFactory->createValueRelation(
                    $childRelation['child_template_pid'],
                    $childRelation['values_ids']
                );
            }
        }

        return $result;
    }
}
