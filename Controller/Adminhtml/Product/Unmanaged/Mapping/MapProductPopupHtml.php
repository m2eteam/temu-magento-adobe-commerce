<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Product\Unmanaged\Mapping;

class MapProductPopupHtml extends \M2E\Temu\Controller\Adminhtml\AbstractListing
{
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository,
        \M2E\Temu\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->unmanagedRepository = $unmanagedRepository;
    }

    public function execute()
    {
        $unmanagedId = $this->getRequest()->getParam('unmanaged_product_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $unmanagedProduct = $this->unmanagedRepository->findById((int)$unmanagedId);

        $block = $this->getLayout()->createBlock(
            \M2E\Temu\Block\Adminhtml\Listing\Mapping\View::class,
            '',
            [
                'data' => [
                    'unmanaged_product_id' => $unmanagedId,
                    'account_id' => $accountId,
                    'grid_url' => \M2E\Temu\Model\UnmanagedProduct\Ui\UrlHelper::PATH_UNMANAGED_MAP_GRID,
                    'product_type' => $this->getAvailableProductTypes($unmanagedProduct),
                ]
            ]
        );

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    private function getAvailableProductTypes(?\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct): array
    {
        if ($unmanagedProduct->isSimple()) {
            return [\M2E\Temu\Helper\Magento\Product::TYPE_SIMPLE];
        }

        return [\M2E\Temu\Helper\Magento\Product::TYPE_CONFIGURABLE];
    }
}
