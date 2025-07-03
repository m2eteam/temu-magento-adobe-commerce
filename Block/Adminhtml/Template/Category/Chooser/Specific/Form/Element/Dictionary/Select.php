<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element\Dictionary;

class Select extends \Magento\Framework\Data\Form\Element\Select
{
    public function getHtmlAttributes()
    {
        $result = parent::getHtmlAttributes();
        $result[] = 'data-max-rows';

        return $result;
    }
}
