<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Listing\Wizard\CategoryValidation;

class Validate extends \M2E\Temu\Controller\Adminhtml\AbstractListing
{
    use \M2E\Temu\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private const MAX_PRODUCT_COUNT_FOR_CATEGORY_VALIDATE = 100;

    private \M2E\Temu\Model\Listing\Wizard\Repository $wizardRepository;
    private \M2E\Temu\Model\Listing\Wizard\CategoryValidation\Validator $attributeValidator;
    private \M2E\Temu\Model\Listing\Wizard\CategoryValidation\ValidatorFactory $validateFactory;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard\Repository $wizardRepository,
        \M2E\Temu\Model\Listing\Wizard\CategoryValidation\ValidatorFactory $validateFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        $context = null
    ) {
        parent::__construct($context);
        $this->wizardRepository = $wizardRepository;
        $this->validateFactory = $validateFactory;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $wizard = $this->wizardRepository->get($id);
        $validator = $this->validateFactory->create($wizard);

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
