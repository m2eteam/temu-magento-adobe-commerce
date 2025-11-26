<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class GetSelectedCategoryDetails extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Tree\Repository $treeRepository;
    private \M2E\Temu\Model\Category\Tree\PathBuilder $pathBuilder;
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;

    public function __construct(
        \M2E\Temu\Model\Category\Tree\Repository $treeRepository,
        \M2E\Temu\Model\Category\Tree\PathBuilder $pathBuilder,
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository
    ) {
        parent::__construct();

        $this->treeRepository = $treeRepository;
        $this->pathBuilder = $pathBuilder;
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    public function execute()
    {
        $region = $this->getRegionFromRequest();
        $categoryId = $this->getCategoryIdFromRequest();
        $dictionaryId = $this->getDictionaryIdFromRequest();

        if ($dictionaryId !== null) {
            $category = $this->categoryDictionaryRepository->get($dictionaryId);

            $this->setJsonContent([
                'path' => $category->getPath(),
                'interface_path' => sprintf(
                    '%s<br>%s (%s)',
                    $category->getTitle(),
                    $category->getPath(),
                    $category->getCategoryId()
                ),
            ]);

            return $this->getResult();
        }

        $category = $this->treeRepository->getCategoryByRegionAndCategoryId($region, $categoryId);
        if ($category === null) {
            throw new \M2E\Temu\Model\Exception\Logic(
                sprintf('Not found category "%s" for region "%s"', $categoryId, $region)
            );
        }

        $path = $this->pathBuilder->getPath($category);
        $details = [
            'path' => $path,
            'interface_path' => sprintf('%s (%s)', $path, $categoryId),
        ];

        $this->setJsonContent($details);

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
