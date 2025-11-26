<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class CreateService
{
    private \M2E\Temu\Model\Category\Tree\Repository $categoryTreeRepository;
    private \M2E\Temu\Model\Category\DictionaryFactory $dictionaryFactory;
    private \M2E\Temu\Model\Category\Tree\PathBuilder $pathBuilder;
    private \M2E\Temu\Model\Category\Dictionary\AttributeService $attributeService;
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private \M2E\Temu\Model\Category\Dictionary\TitleService $titleService;

    public function __construct(
        \M2E\Temu\Model\Category\DictionaryFactory $dictionaryFactory,
        \M2E\Temu\Model\Category\Dictionary\AttributeService $attributeService,
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Model\Category\Tree\Repository $categoryTreeRepository,
        \M2E\Temu\Model\Category\Tree\PathBuilder $pathBuilder,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\Category\Dictionary\TitleService $titleService
    ) {
        $this->dictionaryFactory = $dictionaryFactory;
        $this->attributeService = $attributeService;
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->pathBuilder = $pathBuilder;
        $this->categoryTreeRepository = $categoryTreeRepository;
        $this->accountRepository = $accountRepository;
        $this->titleService = $titleService;
    }

    public function create(
        string $region,
        int $categoryId
    ): \M2E\Temu\Model\Category\Dictionary {
        $treeNode = $this->categoryTreeRepository
            ->getCategoryByRegionAndCategoryId($region, $categoryId);

        if ($treeNode === null) {
            throw new \M2E\Temu\Model\Exception\Logic('Not found category tree');
        }
        $account = $this->getAccountForRegion($region);
        $categoryData = $this->attributeService->getCategoryDataFromServer($region, $categoryId, $account);

        $productAttributes = $this->attributeService->getProductAttributes($categoryData);
        $salesAttributes = $this->attributeService->getSalesAttributes($categoryData);
        $totalProductAttributes = $this->attributeService->getTotalProductAttributes($categoryData);
        $totalSalesAttributes = $this->attributeService->getTotalSalesAttributes($categoryData);
        $hasRequiredProductAttributes = $this->attributeService->getHasRequiredAttributes($categoryData);
        $hasRequiredSalesAttributes = $this->attributeService->getHasRequiredSalesAttributes($categoryData);
        $path = $this->pathBuilder->getPath($treeNode);

        $dictionary = $this->dictionaryFactory->create()->create(
            $region,
            $categoryId,
            $this->titleService->getUnique($region, $categoryId, $path),
            $path,
            $salesAttributes,
            $productAttributes,
            $categoryData->getRules(),
            [],
            $totalProductAttributes,
            $hasRequiredProductAttributes,
            $totalSalesAttributes,
            $hasRequiredSalesAttributes
        );

        $this->categoryDictionaryRepository->create($dictionary);

        return $dictionary;
    }

    private function getAccountForRegion(string $region): string
    {
        $account = $this->accountRepository->findFirstForRegion($region);
        if ($account === null) {
            throw new \M2E\Temu\Model\Exception\Logic('Not found account');
        }

        return $account->getServerHash();
    }
}
