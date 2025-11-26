<?php

namespace M2E\Temu\Controller\Adminhtml\Category;

use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Edit;

class GetChooserEditHtml extends \M2E\Temu\Controller\Adminhtml\AbstractCategory
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        $context = null
    ) {
        parent::__construct($context);
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    public function execute()
    {
        $selectedValue = $this->getRequest()->getParam('selected_value');
        $selectedPath = $this->getRequest()->getParam('selected_path');
        $viewMode = $this->getRequest()->getParam('view_mode', Edit::WITHOUT_TABS_VIEW_MODE);
        $dictionaryId = $this->getRequest()->getParam('dictionary_id');

        /** @var Edit $editBlock */
        $editBlock = $this->getLayout()->createBlock(Edit::class);
        $editBlock->setData(Edit::VIEW_MODE_KEY, $viewMode);

        if (
            !empty($selectedPath)
            && !empty($selectedValue)
        ) {
            $title = '';
            if (!empty($dictionaryId)) {
                $categoryDictionary = $this->categoryDictionaryRepository->get((int)$dictionaryId);
                $title = $categoryDictionary->getTitle();
            }
            $editBlock->setSelectedCategory($selectedValue, $selectedPath, $title);
        }

        $this->setAjaxContent($editBlock->toHtml());

        return $this->getResult();
    }
}
