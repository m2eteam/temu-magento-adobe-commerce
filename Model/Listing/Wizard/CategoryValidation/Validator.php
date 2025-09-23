<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard\CategoryValidation;

class Validator
{
    /** @var \M2E\Temu\Model\Category\Attribute\ValidateMagentoProduct[] */
    private array $validatorsByCategoryId = [];

    private \M2E\Temu\Model\Listing\Wizard\Manager $wizardManager;
    private \M2E\Temu\Model\Listing\Wizard\Repository $wizardRepository;
    private \M2E\Temu\Model\Category\Attribute\ValidateMagentoProductFactory $validateMagentoProductFactory;

    public function __construct(
        \M2E\Temu\Model\Listing\Wizard\Manager $wizardManager,
        \M2E\Temu\Model\Listing\Wizard\Repository $wizardRepository,
        \M2E\Temu\Model\Category\Attribute\ValidateMagentoProductFactory $validateMagentoProductFactory
    ) {
        $this->wizardManager = $wizardManager;
        $this->wizardRepository = $wizardRepository;
        $this->validateMagentoProductFactory = $validateMagentoProductFactory;
    }

    public function processChunk(int $productLimit): Result
    {
        $productToValidate = $this->wizardManager->findProductsForValidateCategoryAttributes($productLimit);

        $errorProductCount = 0;
        foreach ($productToValidate as $product) {
            /** @var \M2E\Temu\Model\Category\Dictionary $categoryDictionary */
            $categoryDictionary = $product->getCategoryDictionary();

            $validator = $this->getValidator($categoryDictionary);

            $errors = $validator->validateProduct($product->getMagentoProduct());

            if (!empty($errors)) {
                $product->markCategoryAttributesAsInvalid($errors);
                $errorProductCount++;
            } else {
                $product->markCategoryAttributesAsValid();
            }

            $this->wizardRepository->saveProduct($product);
        }

        return new \M2E\Temu\Model\Listing\Wizard\CategoryValidation\Result(
            $this->isAllValidate(count($productToValidate), $productLimit),
            count($productToValidate),
            $errorProductCount,
            $this->wizardManager->getProductsCount()
        );
    }

    private function getValidator(
        \M2E\Temu\Model\Category\Dictionary $categoryDictionary
    ): \M2E\Temu\Model\Category\Attribute\ValidateMagentoProduct {
        if (isset($this->validatorsByCategoryId[$categoryDictionary->getId()])) {
            return $this->validatorsByCategoryId[$categoryDictionary->getId()];
        }

        return $this->validatorsByCategoryId[$categoryDictionary->getId()] = $this->validateMagentoProductFactory
            ->createWithCategory($categoryDictionary);
    }

    private function isAllValidate(int $countProductsToValidate, int $limit): bool
    {
        if ($countProductsToValidate === 0) {
            return true;
        }

        return $countProductsToValidate < $limit;
    }
}
