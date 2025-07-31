<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct;

use M2E\Temu\Model\ResourceModel\UnmanagedProduct\VariantSku as UnmanagedVariantSkuResource;

class VariantSku extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    private ?\M2E\Temu\Model\Magento\Product\Cache $magentoProductModel = null;
    private \M2E\Temu\Model\UnmanagedProduct\VariantSku\SalesAttributeFactory $salesAttributeFactory;
    private \M2E\Temu\Model\Magento\Product\CacheFactory $productCacheFactory;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProduct\VariantSku\SalesAttributeFactory $salesAttributeFactory,
        \M2E\Temu\Model\Magento\Product\CacheFactory $productCacheFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $registry);

        $this->salesAttributeFactory = $salesAttributeFactory;
        $this->productCacheFactory = $productCacheFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\M2E\Temu\Model\ResourceModel\UnmanagedProduct\VariantSku::class);
    }

    public function create(
        \M2E\Temu\Model\UnmanagedProduct $product,
        int $status,
        string $skuId,
        ?string $sku,
        string $imageUrl,
        int $qty,
        float $price,
        float $retailPrice,
        string $currencyCode,
        \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification $specification,
        string $qtyRequestTime,
        string $priceRequestTime
    ): self {
        $this
            ->setData(UnmanagedVariantSkuResource::COLUMN_PRODUCT_ID, $product->getId())
            ->setAccountId($product->getAccountId())
            ->setStatus($status)
            ->setImageUrl($imageUrl)
            ->setSkuId($skuId)
            ->setSku($sku)
            ->setQty($qty)
            ->setPrice($price)
            ->setRetailPrice($retailPrice)
            ->setCurrency($currencyCode)
            ->setSpecification($specification)
            ->setData(UnmanagedVariantSkuResource::COLUMN_QTY_ACTUALIZE_DATE, $qtyRequestTime)
            ->setData(UnmanagedVariantSkuResource::COLUMN_PRICE_ACTUALIZE_DATE, $priceRequestTime);

        return $this;
    }

    public function setAccountId(int $accountId): VariantSku
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_ACCOUNT_ID, $accountId);

        return $this;
    }

    public function getProductId(): int
    {
        return (int)$this->getData(UnmanagedVariantSkuResource::COLUMN_PRODUCT_ID);
    }

    public function unmapVariant(): void
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID, null);
    }

    public function mapToMagentoProduct(int $magentoProductId): void
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID, $magentoProductId);
    }

    public function getMagentoProductId(): ?int
    {
        return (int)$this->getData(UnmanagedVariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function setSkuId(string $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_SKU_ID, $value);

        return $this;
    }

    public function getSkuId(): string
    {
        return (string)$this->getData(UnmanagedVariantSkuResource::COLUMN_SKU_ID);
    }

    public function hasMagentoProductId(): bool
    {
        return !empty($this->getMagentoProductId());
    }

    public function setSku(?string $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_SKU, $value);

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->getData(UnmanagedVariantSkuResource::COLUMN_SKU);
    }

    public function setImageUrl(string $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_IMAGE_URL, $value);

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->getData(UnmanagedVariantSkuResource::COLUMN_IMAGE_URL);
    }

    public function setPrice(float $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_PRICE, $value);

        return $this;
    }

    public function getPrice(): float
    {
        return (float)$this->getData(UnmanagedVariantSkuResource::COLUMN_PRICE);
    }

    public function setRetailPrice(float $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_RETAIL_PRICE, $value);

        return $this;
    }

    public function getRetailPrice(): float
    {
        return (float)$this->getData(UnmanagedVariantSkuResource::COLUMN_RETAIL_PRICE);
    }

    public function setCurrency(string $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_CURRENCY, $value);

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->getData(UnmanagedVariantSkuResource::COLUMN_CURRENCY);
    }

    public function setQty(int $value): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_QTY, $value);

        return $this;
    }

    public function getQty(): int
    {
        return (int)$this->getData(UnmanagedVariantSkuResource::COLUMN_QTY);
    }

    public function setStatus(int $status): self
    {
        $this->setData(UnmanagedVariantSkuResource::COLUMN_STATUS, $status);

        return $this;
    }

    public function getStatus(): int
    {
        return (int)$this->getData(UnmanagedVariantSkuResource::COLUMN_STATUS);
    }

    public function setSpecification(\M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification $specification): self
    {
        $this->setData(
            UnmanagedVariantSkuResource::COLUMN_SPECIFICATION,
            strip_tags(json_encode($specification->toArray()))
        );

        return $this;
    }

    public function getSpecification(): VariantSku\Specification
    {
        $specification = $this->getData(UnmanagedVariantSkuResource::COLUMN_SPECIFICATION);

        $rawSpecification = json_decode($specification, true);

        $specific = [];
        foreach ($rawSpecification['specific'] as $item) {
            $specific[] = new \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specific(
                $item['id'],
                $item['title'] ?: '',
                $item['value_id'],
                $item['value_title'] ?: ''
            );
        }

        return new \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specification(
            $rawSpecification['title'],
            $specific
        );
    }

    /**
     * @return \M2E\Temu\Model\Magento\Product\Cache
     * @throws \M2E\Temu\Model\Exception
     */
    public function getMagentoProduct(): ?\M2E\Temu\Model\Magento\Product\Cache
    {
        if ($this->magentoProductModel) {
            return $this->magentoProductModel;
        }

        if (!$this->hasMagentoProductId()) {
            throw new \M2E\Temu\Model\Exception('Product id is not set');
        }

        return $this->magentoProductModel = $this->productCacheFactory->create()
                                                                      ->setStoreId($this->getRelatedStoreId())
                                                                      ->setProductId($this->getMagentoProductId());
    }

    public function getQtyActualizeDate(): ?\DateTime
    {
        $value = $this->getData(UnmanagedVariantSkuResource::COLUMN_QTY_ACTUALIZE_DATE);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createDateGmt($value);
    }

    public function getPriceActualizeDate(): ?\DateTime
    {
        $value = $this->getData(UnmanagedVariantSkuResource::COLUMN_PRICE_ACTUALIZE_DATE);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createDateGmt($value);
    }
}
