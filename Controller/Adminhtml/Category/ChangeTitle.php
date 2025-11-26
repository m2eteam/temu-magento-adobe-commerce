<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class ChangeTitle extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private \M2E\Temu\Model\Category\Dictionary\UpdateTitle $updateTitle;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Model\Category\Dictionary\UpdateTitle $updateTitle
    ) {
        parent::__construct();
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->updateTitle = $updateTitle;
    }

    public function execute()
    {
        try {
            $this->updateTitle
                ->execute($this->getDictionaryFromRequest(), $this->getTitleFromRequest());
        } catch (\Throwable $e) {
            $this->setJsonContent([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return $this->getResult();
        }

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    private function getDictionaryFromRequest(): \M2E\Temu\Model\Category\Dictionary
    {
        $dictionaryId = $this->getRequest()->getParam('dictionary_id');
        if (empty($dictionaryId)) {
            throw new \M2E\Temu\Model\Exception\Logic('Required parameter "dictionary_id" is missing.');
        }

        return $this->categoryDictionaryRepository->get((int)$dictionaryId);
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    private function getTitleFromRequest(): string
    {
        $title = $this->getRequest()->getParam('title');
        if (empty($title)) {
            throw new \M2E\Temu\Model\Exception\Logic('Required parameter "title" is missing.');
        }

        return (string)$title;
    }
}
