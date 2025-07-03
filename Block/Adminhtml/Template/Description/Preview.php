<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Template\Description;

use M2E\Temu\Block\Adminhtml\Magento\AbstractBlock;

class Preview extends AbstractBlock
{
    protected $_template = 'template/description/preview.phtml';

    protected function _construct(): void
    {
        parent::_construct();

        $this->css->addFile('temu/template.css');
    }
}
