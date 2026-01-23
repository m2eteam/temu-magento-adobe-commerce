<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Category;

class CategoryChooser extends \M2E\Temu\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'category/category_chooser.phtml';

    private ?\M2E\Temu\Model\Category\Dictionary $categoryDictionary;

    public function __construct(
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        ?\M2E\Temu\Model\Category\Dictionary $categoryDictionary,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->categoryDictionary = $categoryDictionary;
    }

    public function getSelectedCategory(): ?string
    {
        if (!$this->isExistCategoryDictionary()) {
            return null;
        }

        return $this->categoryDictionary->getCategoryId();
    }

    public function getSelectedDictionaryId(): ?int
    {
        if (!$this->isExistCategoryDictionary()) {
            return null;
        }

        return $this->categoryDictionary->getId();
    }

    public function isExistCategoryDictionary(): bool
    {
        if ($this->categoryDictionary === null) {
            return false;
        }

        if ($this->categoryDictionary->isObjectNew()) {
            return false;
        }

        return true;
    }
}
