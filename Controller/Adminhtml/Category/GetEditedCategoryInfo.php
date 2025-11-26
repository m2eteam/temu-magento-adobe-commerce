<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class GetEditedCategoryInfo extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private \M2E\Temu\Model\Category\Dictionary\CreateService $categoryDictionaryCreateService;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Model\Category\Dictionary\CreateService $categoryDictionaryCreateService
    ) {
        parent::__construct();
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->categoryDictionaryCreateService = $categoryDictionaryCreateService;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function execute()
    {
        $categoryId = $this->getCategoryIdFromRequest();
        $dictionaryId = $this->getDictionaryIdFromRequest();
        $region = $this->getRegionFromRequest();

        try {
            if ($dictionaryId !== null) {
                $dictionary = $this->categoryDictionaryRepository
                    ->get($dictionaryId);
            } else {
                $dictionary = $this->categoryDictionaryCreateService
                    ->create($region, $categoryId);
            }
        } catch (\Throwable $e) {
            $this->setJsonContent([
                'success' => false,
                'message' => $e->getMessage()
            ]);

            return $this->getResult();
        }

        $this->setJsonContent([
            'success' => true,
            'dictionary_id' => $dictionary->getId(),
            'dictionary_title' => $dictionary->getTitle(),
            'is_all_required_attributes_filled' => $dictionary->isAllRequiredAttributesFilled(),
            'path' => $dictionary->getPath(),
            'value' => $dictionary->getCategoryId(),
        ]);

        return $this->getResult();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    private function getCategoryIdFromRequest(): int
    {
        $categoryId = $this->getRequest()->getParam('category_id');
        if (empty($categoryId)) {
            throw new \M2E\Temu\Model\Exception\Logic('Required parameter "category_id" is missing.');
        }

        return (int)$categoryId;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    private function getRegionFromRequest(): string
    {
        $region = $this->getRequest()->getParam('region');
        if (empty($region)) {
            throw new \M2E\Temu\Model\Exception\Logic('Required parameter "region" is missing.');
        }

        return (string)$region;
    }

    private function getDictionaryIdFromRequest(): ?int
    {
        $dictionaryId = $this->getRequest()->getParam('dictionary_id');
        if (empty($dictionaryId)) {
            return null;
        }

        return (int)$dictionaryId;
    }
}
