<?php

declare(strict_types=1);

namespace M2E\Temu\Model;

class UnmanagedProductFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createEmpty(): UnmanagedProduct
    {
        return $this->objectManager->create(UnmanagedProduct::class);
    }

    public function createFromChannel(\M2E\Temu\Model\Channel\Product $channelProduct): UnmanagedProduct
    {
        return $this->create(
            $channelProduct->getAccountId(),
            $channelProduct->getChannelProductId(),
            $channelProduct->getStatus(),
            \M2E\Core\Helper\Data::escapeHtml(
                strip_tags($channelProduct->getTitle())
            ),
            \M2E\Core\Helper\Data::escapeHtml(
                strip_tags($channelProduct->getImageUrl(),)
            ),
            $channelProduct->getShippingTemplateId(),
            $channelProduct->getCategoryId(),
            $channelProduct->getIncompleteReason()
        );
    }

    private function create(
        int $accountId,
        string $channelProductId,
        int $status,
        string $title,
        string $imageUrl,
        int $deliveryTemplateId,
        int $categoryId,
        ?string $incompleteReason
    ): UnmanagedProduct {
        $object = $this->createEmpty();

        $object->create(
            $accountId,
            $channelProductId,
            $status,
            $title,
            $imageUrl,
            $deliveryTemplateId,
            $categoryId,
            $incompleteReason
        );

        return $object;
    }
}
