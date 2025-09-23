<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category\Attribute\Validation;

class Validate extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private const MAX_PRODUCT_COUNT_FOR_CATEGORY_VALIDATE = 100;

    private \M2E\Temu\Model\Product\Category\Attribute\ValidatorFactory $attributeValidatorFactory;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;

    public function __construct(
        \M2E\Temu\Model\Product\Category\Attribute\ValidatorFactory $attributeValidatorFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        $context = null
    ) {
        parent::__construct($context);
        $this->attributeValidatorFactory = $attributeValidatorFactory;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        $categoryId = (int)$this->getRequest()->getParam('template_category_id');

        if (empty($categoryId)) {
            return $this->createJsonResponse(true, 0, 0, 0);
        }

        $validator = $this->attributeValidatorFactory->createByCategoryDictionaryId($categoryId);
        $result = $validator->processChunk(self::MAX_PRODUCT_COUNT_FOR_CATEGORY_VALIDATE);

        return $this->createJsonResponse(
            $result->isAllCompleted(),
            $result->getProcessedProductCount(),
            $result->getErrorProductCount(),
            $result->getTotalProductCount(),
        );
    }

    private function createJsonResponse(
        bool $isAllCompleted,
        int $processedProductCount,
        int $errorProductCount,
        int $totalProductCount
    ): \Magento\Framework\Controller\Result\Json {
        return $this->jsonResultFactory
            ->create()
            ->setData(
                [
                    'is_all_complete' => $isAllCompleted,
                    'processed_product_count' => $processedProductCount,
                    'error_product_count' => $errorProductCount,
                    'total_product_count' => $totalProductCount,
                ],
            );
    }
}
