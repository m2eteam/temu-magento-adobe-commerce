<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Category\Attribute;

class Validator
{
    /** @var \M2E\Temu\Model\Category\Attribute\ValidateMagentoProduct[] */
    private array $validatorsByCategoryId = [];
    private int $categoryDictionaryId;
    private \M2E\Temu\Model\Category\Attribute\ValidateMagentoProductFactory $validateMagentoProductFactory;
    private \M2E\Temu\Model\Product\Repository $productRepository;
    /** @var \M2E\Temu\Model\Product\Category\Attribute\ValidateManager */
    private ValidateManager $validateManager;

    public function __construct(
        int $categoryDictionaryId,
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\Category\Attribute\ValidateMagentoProductFactory $validateMagentoProductFactory,
        \M2E\Temu\Model\Product\Category\Attribute\ValidateManager $validateManager
    ) {
        $this->categoryDictionaryId = $categoryDictionaryId;
        $this->productRepository = $productRepository;
        $this->validateMagentoProductFactory = $validateMagentoProductFactory;
        $this->validateManager = $validateManager;
    }

    public function processChunk(int $productLimit): Result
    {
        $productToValidate = $this->productRepository->findProductsForValidateCategoryAttributes(
            $this->categoryDictionaryId,
            $productLimit
        );

        $errorProductCount = 0;
        foreach ($productToValidate as $product) {
            $categoryDictionary = $product->getCategoryDictionary();

            $validator = $this->getValidator($categoryDictionary);

            $errors = $validator->validateProduct($product->getMagentoProduct());

            if (!empty($errors)) {
                $this->validateManager->markProductAsNotValid($product, $errors);
                $errorProductCount++;
            } else {
                $this->validateManager->markProductAsValid($product);
            }
        }

        return new Result(
            $this->isAllValidate(count($productToValidate), $productLimit),
            count($productToValidate),
            $errorProductCount,
            $this->productRepository->getCountProductsByCategoryId($this->categoryDictionaryId)
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

    private function isAllValidate(int $countNotCategoryValidateProducts, int $limit): bool
    {
        if ($countNotCategoryValidateProducts === 0) {
            return true;
        }

        return $countNotCategoryValidateProducts < $limit;
    }
}
