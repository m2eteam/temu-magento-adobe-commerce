<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Category\Attribute;

class ValidatorFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createByCategoryDictionaryId(int $categoryDictionaryId): Validator
    {
        return $this->objectManager->create(Validator::class, [
            'categoryDictionaryId' => $categoryDictionaryId
        ]);
    }
}
