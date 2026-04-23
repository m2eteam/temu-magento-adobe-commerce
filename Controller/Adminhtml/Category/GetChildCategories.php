<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class GetChildCategories extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\CategoryTreeLoader $categoryTreeLoader;

    public function __construct(
        \M2E\Temu\Model\Category\CategoryTreeLoader $categoryTreeLoader
    ) {
        parent::__construct();

        $this->categoryTreeLoader = $categoryTreeLoader;
    }

    public function execute()
    {
        $region = $this->getRequest()->getParam('region');
        $parentCategoryId = $this->getRequest()->getParam('parent_category_id');
        $parentCategoryId = !empty($parentCategoryId) ? (int)$parentCategoryId : null;

        try {
            $categories = $this->categoryTreeLoader
                ->getCategories($region, $parentCategoryId);

            $response = [
                'success' => true,
                'message' => null,
            ];
            foreach ($categories as $category) {
                $response['categories'][] = [
                    'category_id' => $category->getCategoryId(),
                    'title' => $category->getTitle(),
                    'is_leaf' => (int)$category->isLeaf()
                ];
            }

            $this->setJsonContent($response);
        } catch (\Throwable $exception) {
            $this->setJsonContent([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->getResult();
    }
}
