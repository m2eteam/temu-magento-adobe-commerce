<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product;

use M2E\Temu\Model\ResourceModel\Product\VariantSku as VariantSkuResource;

class VariantSku extends \M2E\Temu\Model\ActiveRecord\AbstractModel implements
    \M2E\Temu\Model\ProductInterface
{
    private int $calculatedQty;
    private \M2E\Temu\Model\Product $product;
    private \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory;
    private Repository $productRepository;
    private \M2E\Temu\Model\Product\VariantSku\DataProviderFactory $dataProviderFactory;
    private \M2E\Temu\Model\Product\VariantSku\DataProvider $dataProvider;
    private \M2E\Temu\Model\Magento\Product\Cache $magentoProduct;

    public function __construct(
        \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory,
        Repository $productRepository,
        \M2E\Temu\Model\Product\VariantSku\DataProviderFactory $dataProviderFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $registry);

        $this->magentoProductFactory = $magentoProductFactory;
        $this->productRepository = $productRepository;
        $this->dataProviderFactory = $dataProviderFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(VariantSkuResource::class);
    }

    public function init(\M2E\Temu\Model\Product $product, int $magentoProductId): self
    {
        $this
            ->setData(VariantSkuResource::COLUMN_PRODUCT_ID, $product->getId())
            ->setMagentoProductId($magentoProductId);

        $product->addVariant($this);
        $this->initProduct($product);

        return $this;
    }

    public function fillFromUnmanagedVariant(
        \M2E\Temu\Model\UnmanagedProduct\VariantSku $unmanagedVariantProduct
    ): self {
        $this
            ->setMagentoProductId($unmanagedVariantProduct->getMagentoProductId())
            ->setSkuId($unmanagedVariantProduct->getSkuId())
            ->setOnlineSku($unmanagedVariantProduct->getSku())
            ->setOnlineQty($unmanagedVariantProduct->getQty())
            ->setOnlinePrice($unmanagedVariantProduct->getPrice())
            ->setStatus($unmanagedVariantProduct->getStatus())
            ->setQtyActualizeDate($unmanagedVariantProduct->getQtyActualizeDate())
            ->setPriceActualizeDate($unmanagedVariantProduct->getPriceActualizeDate());

        return $this;
    }

    // ----------------------------------------

    public function initProduct(\M2E\Temu\Model\Product $product): void
    {
        $this->product = $product;
    }

    // ----------------------------------------

    public function getListing(): \M2E\Temu\Model\Listing
    {
        return $this->getProduct()->getListing();
    }

    public function getProductId(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_PRODUCT_ID);
    }

    public function getProduct(): \M2E\Temu\Model\Product
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->product ?? ($this->product = $this->productRepository->get($this->getProductId()));
    }

    public function getSellingFormatTemplate(): \M2E\Temu\Model\Policy\SellingFormat
    {
        return $this->getProduct()->getSellingFormatTemplate();
    }

    public function getMagentoProduct(): \M2E\Temu\Model\Magento\Product\Cache
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoProduct)) {
            $this->magentoProduct = $this->magentoProductFactory->create();
            $this->magentoProduct->setProductId($this->getMagentoProductId());
            $this->magentoProduct->setStoreId($this->getListing()->getStoreId());
            $this->magentoProduct->setStatisticId($this->getId());
        }

        return $this->magentoProduct;
    }

    private function setMagentoProductId(int $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID, $value);

        return $this;
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function setSkuId(string $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_SKU_ID, $value);

        return $this;
    }

    public function getSkuId(): string
    {
        return (string)$this->getData(VariantSkuResource::COLUMN_SKU_ID);
    }

    public function isStatusNotListed(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Product::STATUS_NOT_LISTED;
    }

    public function isStatusListed(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Product::STATUS_LISTED;
    }

    public function isStatusInactive(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Product::STATUS_INACTIVE;
    }

    public function changeStatusToNoListed(): self
    {
        $this->setStatus(\M2E\Temu\Model\Product::STATUS_NOT_LISTED)
             ->resetSkuId()
             ->resetOnlineData();

        return $this;
    }

    public function changeStatusToListed(): self
    {
        $this->setStatus(\M2E\Temu\Model\Product::STATUS_LISTED);

        return $this;
    }

    public function changeStatusToInactive(): self
    {
        $this->setStatus(\M2E\Temu\Model\Product::STATUS_INACTIVE);

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->setData(VariantSkuResource::COLUMN_STATUS, $status);

        return $this;
    }

    public function getStatus(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_STATUS);
    }

    private function resetSkuId(): self
    {
        $this->setData(VariantSkuResource::COLUMN_SKU_ID, null);

        return $this;
    }

    public function setOnlineSku(string $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_SKU, $value);

        return $this;
    }

    public function getOnlineSku(): ?string
    {
        return $this->getData(VariantSkuResource::COLUMN_ONLINE_SKU);
    }

    public function setOnlinePrice(float $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_PRICE, $value);

        return $this;
    }

    public function getOnlinePrice(): float
    {
        return (float)$this->getData(VariantSkuResource::COLUMN_ONLINE_PRICE);
    }

    public function setOnlineQty(int $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_QTY, $value);

        return $this;
    }

    public function getOnlineQty(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_ONLINE_QTY);
    }

    public function setOnlineImage(string $image): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_IMAGE, $image);

        return $this;
    }

    public function getOnlineImage(): string
    {
        return (string)$this->getData(VariantSkuResource::COLUMN_ONLINE_IMAGE);
    }

    public function setVariationData(VariantSku\Dto\VariationData $variationData): self
    {
        $this->setData(VariantSkuResource::COLUMN_VARIATION_DATA, $variationData->exportToJson());

        return $this;
    }

    public function getVariationData(): VariantSku\Dto\VariationData
    {
        return (new \M2E\Temu\Model\Product\VariantSku\Dto\VariationData())
            ->importFromJson((string)$this->getData(VariantSkuResource::COLUMN_VARIATION_DATA));
    }

    public function setOnlineReferenceLink(?string $referenceLink): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_REFERENCE_LINK, $referenceLink);

        return $this;
    }

    public function getOnlineReferenceLink(): ?string
    {
        return $this->getData(VariantSkuResource::COLUMN_ONLINE_REFERENCE_LINK);
    }

    public function getDataProvider(): VariantSku\DataProvider
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->dataProvider)) {
            return $this->dataProvider;
        }

        return $this->dataProvider = $this->dataProviderFactory->create($this);
    }

    private function resetOnlineData(): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_SKU, null)
             ->setData(VariantSkuResource::COLUMN_ONLINE_QTY, null)
             ->setData(VariantSkuResource::COLUMN_ONLINE_PRICE, null);

        return $this;
    }

    public function getOnlineData(): VariantSku\OnlineData
    {
        return new \M2E\Temu\Model\Product\VariantSku\OnlineData(
            $this->getId(),
            $this->getOnlineQty(),
            $this->getOnlinePrice(),
            $this->getOnlineSku(),
            $this->getStatus(),
        );
    }

    // ----------------------------------------

    public function getSku(): string
    {
        return $this->getMagentoProduct()->getSku();
    }

    public function getSyncPolicy(): \M2E\Temu\Model\Policy\Synchronization
    {
        return $this->getProduct()->getSynchronizationTemplate();
    }

    public function setQtyActualizeDate(?\DateTime $date): self
    {
        $this->setData(VariantSkuResource::COLUMN_QTY_ACTUALIZE_DATE, $date);

        return $this;
    }

    public function getQtyActualizeDate(): ?\DateTime
    {
        $value = $this->getData(VariantSkuResource::COLUMN_QTY_ACTUALIZE_DATE);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createDateGmt($value);
    }

    public function setPriceActualizeDate(?\DateTime $date): self
    {
        $this->setData(VariantSkuResource::COLUMN_PRICE_ACTUALIZE_DATE, $date);

        return $this;
    }

    public function getPriceActualizeDate(): ?\DateTime
    {
        $value = $this->getData(VariantSkuResource::COLUMN_PRICE_ACTUALIZE_DATE);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createDateGmt($value);
    }

    public function getCategoryDictionary(): \M2E\Temu\Model\Category\Dictionary
    {
        return $this->getProduct()->getCategoryDictionary();
    }

    public function getDescriptionTemplateSource(): \M2E\Temu\Model\Policy\Description\Source
    {
        return $this->getProduct()->getDescriptionTemplate()->getSource($this->getMagentoProduct());
    }
}
