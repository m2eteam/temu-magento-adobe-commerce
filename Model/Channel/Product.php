<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel;

class Product
{
    private int $accountId;
    private string $channelProductId;
    private string $title;
    private string $imageUrl;
    private Product\VariantSku\VariantSkuCollection $variantSkusCollection;
    private int $categoryId;
    private int $shippingTemplateId;
    private int $status;
    private ?string $incompleteReason;

    public function __construct(
        int $accountId,
        string $channelProductId,
        string $title,
        string $imageUrl,
        Product\VariantSku\VariantSkuCollection $variantSkusCollection,
        int $categoryId,
        int $shippingTemplateId,
        int $status,
        ?string $incompleteReason
    ) {
        $this->accountId = $accountId;
        $this->channelProductId = $channelProductId;
        $this->title = $title;
        $this->imageUrl = $imageUrl;
        $this->variantSkusCollection = $variantSkusCollection;
        $this->categoryId = $categoryId;
        $this->shippingTemplateId = $shippingTemplateId;
        $this->status = $status;
        $this->incompleteReason = $incompleteReason;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getChannelProductId(): string
    {
        return $this->channelProductId;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getVariantSkusCollection(): Product\VariantSku\VariantSkuCollection
    {
        return $this->variantSkusCollection;
    }

    public function getShippingTemplateId(): int
    {
        return $this->shippingTemplateId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getIncompleteReason(): ?string
    {
        return $this->incompleteReason;
    }
}
