<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Stop;

class Request extends \M2E\Temu\Model\Product\Action\AbstractRequest
{
    public function getActionData(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings,
        array $params
    ): array {

        $dataProvider = $product->getDataProvider();

        return [
            'id' => $product->getChannelProductId(),
            'skus' => $dataProvider->getVariantSkuIds($variantSettings)
        ];
    }

    protected function getActionMetadata(): array
    {
        return [];
    }
}
