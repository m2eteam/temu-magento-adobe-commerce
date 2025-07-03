<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class AttributeFactory
{
    /**
     * @param \M2E\Temu\Model\Category\Dictionary\Attribute\Value[] $values
     */
    public function createSalesAttribute(
        string $id,
        string $name,
        bool $isSale,
        bool $isRequired,
        bool $isCustomised,
        bool $isMultipleSelected,
        string $typeFormat,
        array $rules,
        int $pid,
        int $refPid,
        int $templatePid,
        ?int $parentSpecId,
        ?int $parentTemplatePid,
        array $values
    ): \M2E\Temu\Model\Category\Dictionary\Attribute\SalesAttribute {
        return new \M2E\Temu\Model\Category\Dictionary\Attribute\SalesAttribute(
            $id,
            $name,
            $isSale,
            $isRequired,
            $isCustomised,
            $isMultipleSelected,
            $typeFormat,
            $rules,
            $pid,
            $refPid,
            $templatePid,
            $parentSpecId,
            $parentTemplatePid,
            $values
        );
    }

    /**
     * @param \M2E\Temu\Model\Category\Dictionary\Attribute\Value[] $values
     */
    public function createProductAttribute(
        string $id,
        string $name,
        bool $isSale,
        bool $isRequired,
        bool $isCustomised,
        bool $isMultipleSelected,
        string $typeFormat,
        array $rules,
        int $pid,
        int $refPid,
        int $templatePid,
        ?int $parentSpecId,
        ?int $parentTemplatePid,
        array $values,
        ?int $multipleSelectedMaxCount
    ): \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute {
        return new \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute(
            $id,
            $name,
            $isSale,
            $isRequired,
            $isCustomised,
            $isMultipleSelected,
            $typeFormat,
            $rules,
            $pid,
            $refPid,
            $templatePid,
            $parentSpecId,
            $parentTemplatePid,
            $values,
            $multipleSelectedMaxCount
        );
    }

    /**
     * @param string $id
     * @param string $name
     * @param int|null $specId
     * @param int|null $groupId
     * @param \M2E\Temu\Model\Category\Dictionary\Attribute\ValueRelation[] $valueRelation
     *
     * @return \M2E\Temu\Model\Category\Dictionary\Attribute\Value
     */
    public function createValue(
        string $id,
        string $name,
        ?int $specId,
        ?int $groupId,
        array $valueRelation
    ): \M2E\Temu\Model\Category\Dictionary\Attribute\Value {
        return new \M2E\Temu\Model\Category\Dictionary\Attribute\Value($id, $name, $specId, $groupId, $valueRelation);
    }

    public function createValueRelation(
        int $childTemplatePid,
        array $valueIds
    ): \M2E\Temu\Model\Category\Dictionary\Attribute\ValueRelation {
        return new \M2E\Temu\Model\Category\Dictionary\Attribute\ValueRelation($childTemplatePid, $valueIds);
    }
}
