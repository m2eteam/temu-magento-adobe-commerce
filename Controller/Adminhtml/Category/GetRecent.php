<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class GetRecent extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryRepository;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryRepository
    ) {
        parent::__construct();

        $this->categoryRepository = $categoryRepository;
    }

    public function execute()
    {
        $region = $this->getRequest()->getParam('region');
        $categories = $this->categoryRepository->getByRegion($region);

        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'dictionary_id' => $category->getId(),
                'category_id' => $category->getCategoryId(),
                'path' => $category->getPathWithCategoryId(),
                'title' => $category->getTitle(),
                'is_valid' => $category->isCategoryValid(),
            ];
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
