<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Category;

class ChangeTitleForm extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(
                \M2E\Temu\Block\Adminhtml\Category\Edit\TitleForm::class,
                '',
                [
                    'categoryDictionary' => $this->categoryDictionaryRepository->get(
                        (int)$this->getRequest()->getParam('dictionary_id')
                    ),
                ],
            )
        );

        return $this->getResult();
    }
}
