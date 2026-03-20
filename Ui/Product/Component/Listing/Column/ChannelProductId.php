<?php

declare(strict_types=1);

namespace M2E\Temu\Ui\Product\Component\Listing\Column;

class ChannelProductId extends \Magento\Ui\Component\Listing\Columns\Column
{
    private \M2E\Temu\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage;

    public function __construct(
        \M2E\Temu\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->productUiRuntimeStorage = $productUiRuntimeStorage;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $product = $this->productUiRuntimeStorage->findProduct((int)$row['product_id']);
            if (empty($product)) {
                continue;
            }

            $row['product_channel_product_id'] = __('N/A');

            $channelProductId = $product->getChannelProductId();

            if ($product->isStatusNotListed()) {
                $row['product_channel_product_id'] = sprintf('<span style="color: gray;">%s</span>', __('Not Listed'));
            }

            if ($channelProductId === '') {
                continue;
            }

            $row['product_channel_product_id'] = $channelProductId;
        }

        return $dataSource;
    }
}
