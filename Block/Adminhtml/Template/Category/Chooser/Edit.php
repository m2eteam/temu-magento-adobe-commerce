<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\Chooser;

class Edit extends \M2E\Temu\Block\Adminhtml\Magento\AbstractContainer
{
    public const WITH_TABS_VIEW_MODE = 'with_tabs';
    public const WITHOUT_TABS_VIEW_MODE = 'without_tabs';
    public const VIEW_MODE_KEY = 'view_mode';

    private array $selectedCategory = [];

    public function _construct()
    {
        parent::_construct();

        $this->setId('temuTemplateCategoryChooserEdit');
        $this->setTemplate('template/category/chooser/edit.phtml');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    protected function _toHtml()
    {
        $viewMode = $this->getData(self::VIEW_MODE_KEY);

        if ($viewMode === null || $viewMode === self::WITHOUT_TABS_VIEW_MODE) {
            return $this->renderWithoutTabs();
        }

        return $this->renderWithTabs();
    }

    private function renderWithTabs(): string
    {
        $tabsContainer = $this->getLayout()->createBlock(
            \M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Tabs::class,
        );
        $tabsContainer->setDestElementId('chooser_tabs_container');

        return '<div id="chooser_container">' .
            parent::_toHtml() .
            $tabsContainer->toHtml() .
            '<div id="chooser_tabs_container"></div></div>';
    }

    private function renderWithoutTabs(): string
    {
        $browseContent = $this->getLayout()->createBlock(
            \M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Tabs\Browse::class,
        )->toHtml();

        return '<div id="chooser_container">' . parent::_toHtml() . $browseContent;
    }

    public function getSelectedCategory(): array
    {
        return $this->selectedCategory;
    }

    public function setSelectedCategory(string $value, string $path, string $title): void
    {
        $this->selectedCategory = [
            'value' => $value,
            'path' => $path,
            'title' => $title,
        ];
    }

    public function getSelectedCategoryPathHtml(): string
    {
        if (
            empty($this->selectedCategory['path'])
            || empty($this->selectedCategory['value'])
        ) {
            return sprintf(
                '<span style="font-style: italic; color: grey">%s</span>',
                __('Not Selected')
            );
        }

        $path = sprintf(
            '%s (%s)',
            $this->selectedCategory['path'],
            $this->selectedCategory['value']
        );

        if ($this->selectedCategory['title']) {
            $path = $this->selectedCategory['title'] . '<br>' . $path;
        }

        return $path;
    }
}
