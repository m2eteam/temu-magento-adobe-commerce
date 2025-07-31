<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct;

class VariantSkuFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createEmpty(): VariantSku
    {
        return $this->objectManager->create(VariantSku::class);
    }

    public function createFromChannel(
        \M2E\Temu\Model\Channel\Product\VariantSku $channelVariant,
        \M2E\Temu\Model\UnmanagedProduct $product
    ): VariantSku {
        return $this->create($channelVariant, $product);
    }

    private function create(
        \M2E\Temu\Model\Channel\Product\VariantSku $channelVariant,
        \M2E\Temu\Model\UnmanagedProduct $product
    ): VariantSku {
        $object = $this->createEmpty();

        $object->create(
            $product,
            $channelVariant->getStatus(),
            $channelVariant->getSkuId(),
            \M2E\Core\Helper\Data::escapeHtml(
                strip_tags((string)$channelVariant->getSku())
            ),
            \M2E\Core\Helper\Data::escapeHtml(
                strip_tags($channelVariant->getImageUrl())
            ),
            $channelVariant->getQty(),
            $channelVariant->getPrice(),
            $channelVariant->getRetailPrice(),
            $channelVariant->getCurrencyCode(),
            $channelVariant->getSpecification(),
            $channelVariant->getQtyRequestTime(),
            $channelVariant->getPriceRequestTime()
        );

        return $object;
    }
}
