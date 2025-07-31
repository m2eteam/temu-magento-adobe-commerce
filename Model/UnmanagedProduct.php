<?php

declare(strict_types=1);

namespace M2E\Temu\Model;

use M2E\Temu\Model\ResourceModel\UnmanagedProduct as UnmanagedProductResource;
use M2E\Temu\Model\UnmanagedProduct\Repository;

class UnmanagedProduct extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    private \M2E\Temu\Model\Account $account;
    private ?\M2E\Temu\Model\Magento\Product\Cache $magentoProductModel = null;
    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private \M2E\Temu\Model\Magento\Product\CacheFactory $productCacheFactory;
    private Repository $unmanagedRepository;
    /** @var \M2E\Temu\Model\UnmanagedProduct\VariantSku[] */
    private array $variants;

    public function __construct(
        Repository $unmanagedRepository,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\Magento\Product\CacheFactory $productCacheFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data,
        );

        $this->unmanagedRepository = $unmanagedRepository;
        $this->productCacheFactory = $productCacheFactory;
        $this->accountRepository = $accountRepository;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(UnmanagedProductResource::class);
    }

    public function create(
        int $accountId,
        string $channelProductId,
        int $status,
        string $title,
        string $imageUrl,
        int $shippingTemplateId,
        int $categoryId
    ): self {
        $this
            ->setData(UnmanagedProductResource::COLUMN_ACCOUNT_ID, $accountId)
            ->setData(UnmanagedProductResource::COLUMN_CHANNEL_PRODUCT_ID, $channelProductId)
            ->setStatus($status)
            ->setTitle($title)
            ->setImageUrl($imageUrl)
            ->setCategoryId($categoryId)
            ->setShippingTemplateId($shippingTemplateId);

        return $this;
    }

    public function getAccount(): \M2E\Temu\Model\Account
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->account)) {
            return $this->account;
        }

        return $this->account = $this->accountRepository->get($this->getAccountId());
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

    // ----------------------------------------

    public function getAccountId(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_ACCOUNT_ID);
    }

    public function hasMagentoProductId(): bool
    {
        return !empty($this->getMagentoProductId());
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function mapToMagentoProduct(int $magentoProductId): void
    {
        $this->setData(UnmanagedProductResource::COLUMN_MAGENTO_PRODUCT_ID, $magentoProductId);
    }

    public function unmapFromMagentoProduct(): void
    {
        $this->setData(UnmanagedProductResource::COLUMN_MAGENTO_PRODUCT_ID, null);
    }

    // ----------------------------------------

    public function getChannelProductId(): string
    {
        return $this->getData(UnmanagedProductResource::COLUMN_CHANNEL_PRODUCT_ID);
    }

    // ----------------------------------------

    public function isStatusListed(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Product::STATUS_LISTED;
    }

    public function isStatusInactive(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Product::STATUS_INACTIVE;
    }

    public function setStatus(int $status): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_STATUS, $status);

        return $this;
    }

    public function getStatus(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_STATUS);
    }

    // ----------------------------------------

    public function setTitle(string $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_TITLE, $value);

        return $this;
    }

    public function getTitle(): string
    {
        return (string)$this->getData(UnmanagedProductResource::COLUMN_TITLE);
    }

    public function getCurrencyCode(): string
    {
        return $this->getData(UnmanagedProductResource::COLUMN_CURRENCY_CODE);
    }

    public function setQty(int $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_QTY, $value);

        return $this;
    }

    public function getQty(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_QTY);
    }

    public function setMinPrice(?float $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_MIN_PRICE, $value);

        return $this;
    }

    public function getMinPrice(): ?float
    {
        $minPrice = $this->getData(UnmanagedProductResource::COLUMN_MIN_PRICE);
        if ($minPrice === null) {
            return null;
        }

        return (float)$minPrice;
    }

    public function setMaxPrice(?float $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_MAX_PRICE, $value);

        return $this;
    }

    public function getMaxPrice(): ?float
    {
        $maxPrice = $this->getData(UnmanagedProductResource::COLUMN_MAX_PRICE);
        if ($maxPrice === null) {
            return null;
        }

        return (float)$maxPrice;
    }

    public function getFirstSkuFromVariants(): ?string
    {
        $this->getVariants();
        if ($this->isSimple()) {
            return reset($this->variants)->getSku();
        }

        return null;
    }

    public function getSkuId(): ?string
    {
        $this->getVariants();
        if ($this->isSimple()) {
            return reset($this->variants)->getSkuId();
        }

        return null;
    }

    public function getPrice(): ?float
    {
        $this->getVariants();
        if ($this->isSimple()) {
            return reset($this->variants)->getPrice();
        }

        return null;
    }

    public function getCategoryId(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_CATEGORY_ID);
    }

    public function setShippingTemplateId(int $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_SHIPPING_TEMPLATE_ID, $value);

        return $this;
    }

    public function getShippingTemplateId(): int
    {
        return (int)$this->getData(UnmanagedProductResource::COLUMN_SHIPPING_TEMPLATE_ID);
    }

    public function setIsSimple(bool $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_IS_SIMPLE, (int)$value);

        return $this;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_IMAGE_URL, $imageUrl);

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->getData(UnmanagedProductResource::COLUMN_IMAGE_URL);
    }

    public function setCategoryId(int $value): self
    {
        $this->setData(UnmanagedProductResource::COLUMN_CATEGORY_ID, $value);

        return $this;
    }

    // ---------------------------------------

    public function isListingCorrectForMove(\M2E\Temu\Model\Listing $listing): bool
    {
        return $listing->getAccountId() === $this->getAccountId();
    }

    public function getFirstVariant(): \M2E\Temu\Model\UnmanagedProduct\VariantSku
    {
        $variants = $this->getVariants();

        return reset($variants);
    }

    /**
     * @return \M2E\Temu\Model\UnmanagedProduct\VariantSku[]
     */
    public function getVariants(): array
    {
        $this->loadVariants();

        return array_values($this->variants);
    }

    public function getSpecificNames(): array
    {
        $names = [];
        $variants = $this->getVariants();

        foreach ($variants as $variant) {
            $specification = $variant->getSpecification();
            foreach ($specification->getSpecific() as $specific) {
                if (empty($specific->getTitle())) {
                    continue;
                }

                if (!in_array($specific->getTitle(), $names)) {
                    $names[] = $specific->getTitle();
                }
            }
        }

        return $names;
    }

    public function isSimple(): bool
    {
        return (bool)$this->getData(UnmanagedProductResource::COLUMN_IS_SIMPLE);
    }

    public function getRelatedStoreId(): int
    {
        return $this->getAccount()->getUnmanagedListingSettings()->getRelatedStoreId();
    }

    // ----------------------------------------

    public function updateFromChannel(\M2E\Temu\Model\Channel\Product $channelProduct): void
    {
        if ($this->getTitle() !== $channelProduct->getTitle()) {
            $this->setTitle($channelProduct->getTitle());
        }

        if ($this->getStatus() !== $channelProduct->getStatus()) {
            $this->setStatus($channelProduct->getStatus());
        }

        $existProductChanged = false;
        foreach ($this->getVariants() as $existVariant) {
            $variantCollection = $channelProduct->getVariantSkusCollection();

            $newVariantSku = $variantCollection->findProductSkuBySkuId($existVariant->getSkuId());

            if (!$newVariantSku) {
                continue;
            }

            $existingVariantChanged = false;

            if ($existVariant->getQty() !== $newVariantSku->getQty()) {
                $existVariant->setQty($newVariantSku->getQty());
                $existingVariantChanged = true;
            }

            if ($existVariant->getPrice() !== $newVariantSku->getPrice()) {
                $existVariant->setPrice($newVariantSku->getPrice());
                $existingVariantChanged = true;
            }

            if ($existVariant->getSku() !== $newVariantSku->getSku()) {
                $existVariant->setSku($newVariantSku->getSku());
                $existingVariantChanged = true;
            }

            if ($existingVariantChanged) {
                $this->unmanagedRepository->saveVariant($existVariant);
                $existProductChanged = true;
            }
        }

        if ($existProductChanged) {
            $this->calculateDataByVariants();
        }
    }

    private function loadVariants(): void
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->variants)) {
            return;
        }

        $variants = [];
        foreach ($this->unmanagedRepository->findVariantsByProduct($this) as $variant) {
            $variants[$variant->getSkuId()] = $variant;
        }

        $this->variants = $variants;
    }

    public function calculateDataByVariants(): self
    {
        $this->setQty($this->getVariantsQtySum());
        $this->setIsSimple(true);

        if (count($this->getVariants()) > 1) {
            $this->setIsSimple(false);

            $this
                ->setMinPrice($this->getMinPriceFromVariants())
                ->setMaxPrice($this->getMaxPriceFromVariants());
        }

        return $this;
    }

    private function getVariantsQtySum(): int
    {
        $result = 0;
        foreach ($this->getVariants() as $variant) {
            $result += $variant->getQty();
        }

        return $result;
    }

    private function getMinPriceFromVariants(): ?float
    {
        $prices = $this->getPricesFromVariantSku();

        if (empty($prices)) {
            return null;
        }

        if (count($prices) === 1) {
            return (float)reset($prices);
        }

        return (float)min($this->getPricesFromVariantSku());
    }

    private function getMaxPriceFromVariants(): ?float
    {
        $prices = $this->getPricesFromVariantSku();

        if (empty($prices)) {
            return null;
        }

        if (count($prices) === 1) {
            return (float)reset($prices);
        }

        return (float)max($this->getPricesFromVariantSku());
    }

    private function getPricesFromVariantSku(): array
    {
        $prices = [];
        foreach ($this->getVariants() as $variant) {
            $prices[] = $variant->getPrice();
        }

        return $prices;
    }
}
