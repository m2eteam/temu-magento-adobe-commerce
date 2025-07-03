<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary\Attribute;

class Serializer
{
    private \M2E\Temu\Model\Category\Dictionary\AttributeFactory $attributeFactory;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\AttributeFactory $attributeFactory
    ) {
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @param SalesAttribute[] $attributes
     */
    public function serializeSalesAttributes(array $attributes): string
    {
        $data = [];
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof SalesAttribute) {
                throw new \LogicException('Invalid attribute instance');
            }

            $values = $this->getValues($attribute);
            $data[] = [
                'id' => $attribute->getId(),
                'name' => $attribute->getName(),
                'is_sale' => $attribute->isSale(),
                'is_required' => $attribute->isRequired(),
                'is_customized' => $attribute->isCustomised(),
                'is_multiple_selected' => $attribute->isMultipleSelected(),
                'type_format' => $attribute->getTypeFormat(),
                'rules' => $attribute->getRules(),
                'pid' => $attribute->getPid(),
                'ref_pid' => $attribute->getRefPid(),
                'template_pid' => $attribute->getTemplatePid(),
                'parent_spec_id' => $attribute->getParentSpecId(),
                'parent_template_pid' => $attribute->getParentTemplatePid(),
                'values' => $values,
            ];
        }

        return json_encode($data);
    }

    /**
     * @return SalesAttribute[]
     */
    public function unSerializeSalesAttributes(string $jsonAttributes): array
    {
        $attributes = [];
        foreach (json_decode($jsonAttributes, true) as $item) {
            $values = $this->createValues($item['values'] ?? []);
            $attributes[] = $this->attributeFactory->createSalesAttribute(
                (string)$item['id'],
                $item['name'],
                $item['is_sale'],
                $item['is_required'],
                $item['is_customized'],
                $item['is_multiple_selected'],
                $item['type_format'],
                $item['rules'],
                $item['pid'],
                $item['ref_pid'],
                $item['template_pid'],
                $item['parent_spec_id'],
                $item['parent_template_pid'] ?? null,
                $values
            );
        }

        return $attributes;
    }

    /**
     * @param ProductAttribute[] $attributes
     *
     * @return string
     */
    public function serializeProductAttributes(array $attributes): string
    {
        $data = [];
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof ProductAttribute) {
                throw new \LogicException('Invalid attribute instance');
            }

            $values = $this->getValues($attribute);
            $data[] = [
                'id' => $attribute->getId(),
                'name' => $attribute->getName(),
                'is_sale' => $attribute->isSale(),
                'is_required' => $attribute->isRequired(),
                'is_customized' => $attribute->isCustomised(),
                'is_multiple_selected' => $attribute->isMultipleSelected(),
                'type_format' => $attribute->getTypeFormat(),
                'rules' => $attribute->getRules(),
                'pid' => $attribute->getPid(),
                'ref_pid' => $attribute->getRefPid(),
                'template_pid' => $attribute->getTemplatePid(),
                'parent_spec_id' => $attribute->getParentSpecId(),
                'parent_template_pid' => $attribute->getParentTemplatePid(),
                'multiple_selected_max_count' => $attribute->getMultipleSelectedMaxCount(),
                'values' => $values,
            ];
        }

        return json_encode($data);
    }

    /**
     * @return SalesAttribute[]
     */
    public function unSerializeProductAttributes(string $jsonAttributes): array
    {
        $attributes = [];
        foreach (json_decode($jsonAttributes, true) as $item) {
            $values = $this->createValues($item['values']);
            $attributes[] = $this->attributeFactory->createProductAttribute(
                (string)$item['id'],
                $item['name'],
                $item['is_sale'],
                $item['is_required'],
                $item['is_customized'],
                $item['is_multiple_selected'],
                $item['type_format'],
                $item['rules'],
                $item['pid'],
                $item['ref_pid'],
                $item['template_pid'],
                $item['parent_spec_id'],
                $item['parent_template_pid'] ?? null,
                $values,
                $item['multiple_selected_max_count'] ?? null
            );
        }

        return $attributes;
    }

    private function getValues(\M2E\Temu\Model\Category\Dictionary\AbstractAttribute $attribute): array
    {
        $values = [];
        foreach ($attribute->getValues() as $value) {
            $values[] = [
                'id' => $value->getId(),
                'name' => $value->getName(),
                'spec_id' => $value->getSpecId(),
                'group_id' => $value->getGroupId(),
                'children_relation' => $this->getValueRelation($value),
            ];
        }

        return $values;
    }

    /**
     * @param array $values
     *
     * @return \M2E\Temu\Model\Category\Dictionary\Attribute\Value[]
     */
    private function createValues(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $valueRelation = $this->createValueRelation($value);
            $result[] = $this->attributeFactory->createValue(
                $value['id'],
                $value['name'],
                $value['spec_id'],
                $value['group_id'],
                $valueRelation
            );
        }

        return $result;
    }

    private function createValueRelation(array $value): array
    {
        $result = [];
        if (!empty($value['children_relation'])) {
            foreach ($value['children_relation'] as $childRelation) {
                if (empty($childRelation)) {
                    continue;
                }
                $result[] = $this->attributeFactory->createValueRelation(
                    $childRelation['child_template_pid'],
                    $childRelation['values_ids']
                );
            }
        }

        return $result;
    }

    private function getValueRelation(Value $value): array
    {
        $result = [];
        foreach ($value->getValueRelation() as $childRelation) {
            $result[] = [
                'child_template_pid' => $childRelation->getChildTemplatePid(),
                'values_ids' => $childRelation->getValueIds(),
            ];
        }

        return $result;
    }
}
