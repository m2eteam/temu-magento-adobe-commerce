<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Product;

class VariantSku
{
    private int $accountId;
    private string $skuId;
    private ?string $sku;
    private string $imageUrl;
    private int $qty;
    private float $basePrice;
    private float $retailPrice;
    private int $status;
    private string $currencyCode;
    private \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification $specification;
    private array $salesAttributes;
    private string $qtyRequestTime;
    private string $priceRequestTime;

    public function __construct(
        int $accountId,
        string $skuId,
        ?string $sku,
        string $imageUrl,
        int $qty,
        float $basePrice,
        float $retailPrice,
        int $status,
        string $currencyCode,
        \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification $specification,
        array $salesAttributes,
        string $qtyRequestTime,
        string $priceRequestTime
    ) {
        $this->accountId = $accountId;
        $this->skuId = $skuId;
        $this->sku = $sku;
        $this->imageUrl = $imageUrl;
        $this->qty = $qty;
        $this->basePrice = $basePrice;
        $this->retailPrice = $retailPrice;
        $this->status = $status;
        $this->currencyCode = $currencyCode;
        $this->specification = $specification;
        $this->salesAttributes = $salesAttributes;
        $this->qtyRequestTime = $qtyRequestTime;
        $this->priceRequestTime = $priceRequestTime;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getSkuId(): string
    {
        return $this->skuId;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getQty(): int
    {
        return $this->qty;
    }

    public function getPrice(): float
    {
        return $this->basePrice;
    }

    public function getRetailPrice(): float
    {
        return $this->retailPrice;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getSpecification(): \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification
    {
        return $this->specification;
    }

    public function getSalesAttributes(): array
    {
        return $this->salesAttributes;
    }

    public function getQtyRequestTime(): string
    {
        return $this->qtyRequestTime;
    }

    public function getPriceRequestTime(): string
    {
        return $this->priceRequestTime;
    }
}
