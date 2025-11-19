<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class AttributeClassifier
{
    private const SAFETY_AND_COMPLIANCE_BRAND = [
        'pid' => 1467,
        'title' => 'Brand',
    ];

    private const SAFETY_AND_COMPLIANCE_OTHER = [
        [
            'id' => '1000001000',
            'title' => 'Country of Origin',
        ],
        [
            'id' => '1000001001',
            'title' => 'Province/Region (for China)',
        ],
    ];

    public function isSafetyComplianceAttribute(
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute
    ): bool {
        return $this->isBrandAttribute($attribute)
            || $this->isOtherSafetyComplianceAttribute($attribute);
    }

    public function isBrandAttribute(
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute
    ): bool {
        return $attribute->getPid() === self::SAFETY_AND_COMPLIANCE_BRAND['pid']
            || $attribute->getName() === self::SAFETY_AND_COMPLIANCE_BRAND['title'];
    }

    public function isOtherSafetyComplianceAttribute(
        \M2E\Temu\Model\Category\Dictionary\Attribute\ProductAttribute $attribute
    ): bool {
        foreach (self::SAFETY_AND_COMPLIANCE_OTHER as $item) {
            if (
                (string)$attribute->getRefPid() === $item['id']
                || $attribute->getName() === $item['title']
            ) {
                return true;
            }
        }

        return false;
    }
}
