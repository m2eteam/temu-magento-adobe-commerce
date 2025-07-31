<?php

declare(strict_types=1);

namespace M2E\Temu\Ui\Product\Component\Unmanaged\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Title extends \Magento\Ui\Component\Listing\Columns\Column
{
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->unmanagedRepository = $unmanagedRepository;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $productTitle = \M2E\Core\Helper\Data::escapeHtml($row['title']);

            $html = sprintf('<p>%s</p>', $productTitle);

            $sku = \M2E\Core\Helper\Data::escapeHtml($this->getSku($row));
            if (!empty($sku)) {
                $html .= $this->renderLine((string)\__('SKU'), $sku);
            }

            $salesAttributesHtml = $this->renderSalesAttributes($row);
            if (!empty($salesAttributesHtml)) {
                $html .= $salesAttributesHtml;
            }

            $row['title'] = $html;
        }

        return $dataSource;
    }

    private function renderLine(string $label, string $value): string
    {
        return sprintf('<p style="margin: 0"><strong>%s:</strong> %s</p>', $label, $value);
    }

    private function getUnmanagedProduct(int $id): \M2E\Temu\Model\UnmanagedProduct
    {
        return $this->unmanagedRepository->get($id);
    }

    private function getSku(array $row): string
    {
        $unmanagedProduct = $this->getUnmanagedProduct((int)$row['id']);

        return \M2E\Core\Helper\Data::escapeHtml($unmanagedProduct->getFirstSkuFromVariants());
    }

    private function renderSalesAttributes(array $row): string
    {
        $unmanagedProduct = $this->getUnmanagedProduct((int)$row['id']);
        if ($unmanagedProduct->isSimple()) {
            return '';
        }

        $salesAttributes = $unmanagedProduct->getSpecificNames();
        if (empty($salesAttributes)) {
            return sprintf(
                '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">%s</div>',
                __('Variation product')
            );
        }

        $configurableAttributes = array_map(
            static function (string $attributeName) {
                return sprintf('<span>%s</span>', $attributeName);
            },
            $salesAttributes
        );

        return sprintf(
            '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">%s</div>',
            implode(', ', $configurableAttributes)
        );
    }
}
