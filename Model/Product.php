<?php

declare(strict_types=1);

namespace M2E\Temu\Model;

use M2E\Temu\Model\ResourceModel\Product as ProductResource;

class Product extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public const ACTION_LIST = 1;
    public const ACTION_RELIST = 2;
    public const ACTION_REVISE = 3;
    public const ACTION_STOP = 4;
    public const ACTION_DELETE = 5;

    public const STATUS_NOT_LISTED = 0;
    public const STATUS_LISTED = 2;
    public const STATUS_INACTIVE = 8;
    public const STATUS_BLOCKED = 6;

    public const STATUS_CHANGER_UNKNOWN = 0;
    public const STATUS_CHANGER_SYNCH = 1;
    public const STATUS_CHANGER_USER = 2;
    public const STATUS_CHANGER_COMPONENT = 3;
    public const STATUS_CHANGER_OBSERVER = 4;

    public const MOVING_LISTING_OTHER_SOURCE_KEY = 'moved_from_listing_other_id';

    public const INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED = 'channel_status_changed';
    public const INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED = 'channel_qty_changed';
    public const INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED = 'channel_price_changed';
    public const INSTRUCTION_TYPE_VARIANT_SKU_REMOVED = 'variant_sku_removed';
    public const INSTRUCTION_TYPE_VARIANT_SKU_ADDED = 'variant_sku_added';

    /** @var \M2E\Temu\Model\Product\VariantSku[] */
    private array $variants;
    private \M2E\Temu\Model\Listing $listing;
    private \M2E\Temu\Model\Magento\Product\Cache $magentoProductModel;
    private \M2E\Temu\Model\Listing\Repository $listingRepository;
    private \M2E\Temu\Model\Product\Repository $productRepository;
    private \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory;
    private \M2E\Temu\Model\Product\DataProvider $dataProvider;
    private \M2E\Temu\Model\Product\DataProviderFactory $dataProviderFactory;
    private \M2E\Temu\Model\Product\Description\RendererFactory $descriptionRendererFactory;
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private ?Category\Dictionary $categoryDictionary = null;

    public function __construct(
        \M2E\Temu\Model\Listing\Repository $listingRepository,
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\Magento\Product\CacheFactory $magentoProductFactory,
        \M2E\Temu\Model\Product\DataProviderFactory $dataProviderFactory,
        \M2E\Temu\Model\Product\Description\RendererFactory $descriptionRendererFactory,
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $registry);

        $this->listingRepository = $listingRepository;
        $this->productRepository = $productRepository;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->dataProviderFactory = $dataProviderFactory;
        $this->descriptionRendererFactory = $descriptionRendererFactory;
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(ProductResource::class);
    }

    // ----------------------------------------

    public function create(Listing $listing, int $magentoProductId, bool $isSimple): self
    {
        $this
            ->setListingId($listing->getId())
            ->setMagentoProductId($magentoProductId)
            ->setStatusNotListed(self::STATUS_CHANGER_USER)
            ->setData(ProductResource::COLUMN_IS_SIMPLE, (int)$isSimple);

        $this->initListing($listing);

        return $this;
    }

    public function fillFromUnmanagedProduct(\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct): self
    {
        $this->setChannelProductId($unmanagedProduct->getChannelProductId())
             ->setStatus($unmanagedProduct->getStatus(), self::STATUS_CHANGER_COMPONENT)
             ->setOnlineTitle($unmanagedProduct->getTitle())
             ->setOnlineQty($unmanagedProduct->getQty())
             ->setOnlineCategoryId($unmanagedProduct->getCategoryId());

        $additionalData = $this->getAdditionalData();
        $additionalData[self::MOVING_LISTING_OTHER_SOURCE_KEY] = $unmanagedProduct->getId();

        $this->setAdditionalData($additionalData);

        return $this;
    }

    // ----------------------------------------

    public function initListing(\M2E\Temu\Model\Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function getListing(): \M2E\Temu\Model\Listing
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->listing)) {
            return $this->listing;
        }

        return $this->listing = $this->listingRepository->get($this->getListingId());
    }

    public function getAccount(): Account
    {
        return $this->getListing()->getAccount();
    }

    public function getDataProvider(): Product\DataProvider
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->dataProvider)) {
            return $this->dataProvider;
        }

        return $this->dataProvider = $this->dataProviderFactory->create($this);
    }

    public function getMagentoProduct(): \M2E\Temu\Model\Magento\Product\Cache
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoProductModel)) {
            $this->magentoProductModel = $this->magentoProductFactory->create();
            $this->magentoProductModel->setProductId($this->getMagentoProductId());
            $this->magentoProductModel->setStoreId($this->getListing()->getStoreId());
            $this->magentoProductModel->setStatisticId($this->getId());
        }

        return $this->magentoProductModel;
    }

    // ----------------------------------------
    public function getListingId(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_LISTING_ID);
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function isSimple(): bool
    {
        return (bool)$this->getData(ProductResource::COLUMN_IS_SIMPLE);
    }

    public function getChannelProductId(): ?string
    {
        return $this->getData(ProductResource::COLUMN_CHANNEL_PRODUCT_ID);
    }

    public function getOnlineQty(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_ONLINE_QTY);
    }

    // ---------------------------------------

    public function isStatusNotListed(): bool
    {
        return $this->getStatus() === self::STATUS_NOT_LISTED;
    }

    public function isStatusBlocked(): bool
    {
        return $this->getStatus() === self::STATUS_BLOCKED;
    }

    public function isStatusListed(): bool
    {
        return $this->getStatus() === self::STATUS_LISTED;
    }

    public function isStatusInactive(): bool
    {
        return $this->getStatus() === self::STATUS_INACTIVE;
    }

    public function setStatusListed(string $channelProductId, int $changer): self
    {
        $this
            ->setStatus(self::STATUS_LISTED, $changer)
            ->setChannelProductId($channelProductId);

        return $this;
    }

    public function setStatusNotListed(int $changer): self
    {
        $this->setStatus(self::STATUS_NOT_LISTED, $changer)
             ->setData(ProductResource::COLUMN_CHANNEL_PRODUCT_ID, null)
             ->setData(ProductResource::COLUMN_ONLINE_TITLE, null)
             ->setData(ProductResource::COLUMN_ONLINE_QTY, null);

        return $this;
    }

    public function setStatusInactive(int $changer): self
    {
        $this->setStatus(self::STATUS_INACTIVE, $changer);

        return $this;
    }

    public function setStatusBlocked(int $changer): self
    {
        $this->setStatus(self::STATUS_BLOCKED, $changer);

        return $this;
    }

    public function setStatus(int $status, int $changer): self
    {
        $this->setData(ProductResource::COLUMN_STATUS, $status)
             ->setStatusChanger($changer)
             ->setData(
                 ProductResource::COLUMN_STATUS_CHANGE_DATE,
                 \M2E\Core\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
             );

        return $this;
    }

    public function getStatus(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_STATUS);
    }

    // ----------------------------------------

    public function isStatusChangerUser(): bool
    {
        return $this->getStatusChanger() === self::STATUS_CHANGER_USER;
    }

    public function getStatusChanger(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_STATUS_CHANGER);
    }

    // ---------------------------------------

    public function setAdditionalData(array $value): self
    {
        $this->setData(ProductResource::COLUMN_ADDITIONAL_DATA, json_encode($value));

        return $this;
    }

    public function getAdditionalData(): array
    {
        $value = $this->getData(ProductResource::COLUMN_ADDITIONAL_DATA);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }

    // ---------------------------------------

    public function isListable(): bool
    {
        return $this->isStatusNotListed() || $this->isStatusInactive();
    }

    public function isRelistable(): bool
    {
        return $this->isStatusInactive();
    }

    public function isRevisable(): bool
    {
        return $this->isStatusListed();
    }

    public function isStoppable(): bool
    {
        return $this->isStatusListed();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getSellingFormatTemplate(): \M2E\Temu\Model\Policy\SellingFormat
    {
        return $this->getListing()->getTemplateSellingFormat();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getSynchronizationTemplate(): \M2E\Temu\Model\Policy\Synchronization
    {
        return $this->getListing()->getTemplateSynchronization();
    }

    // ---------------------------------------

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getSellingFormatTemplateSource(): \M2E\Temu\Model\Policy\SellingFormat\Source
    {
        return $this->getSellingFormatTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \M2E\Temu\Model\Policy\Description
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getDescriptionTemplate(): \M2E\Temu\Model\Policy\Description
    {
        return $this->getListing()->getTemplateDescription();
    }

    public function getRenderedDescription(): string
    {
        return $this->descriptionRendererFactory
            ->create($this)
            ->parseTemplate($this->getDescriptionTemplateSource()->getDescription());
    }

    /**
     * @return \M2E\Temu\Model\Policy\Description\Source
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getDescriptionTemplateSource(): \M2E\Temu\Model\Policy\Description\Source
    {
        return $this->getDescriptionTemplate()->getSource($this->getMagentoProduct());
    }

    public function getShippingTemplate(): ?\M2E\Temu\Model\Policy\Shipping
    {
        return $this->getListing()->getTemplateShipping();
    }

    public function getCategoryDictionary(): Category\Dictionary
    {
        if (isset($this->categoryDictionary)) {
            return $this->categoryDictionary;
        }

        if (!$this->hasCategoryTemplate()) {
            throw new \M2E\Temu\Model\Exception\Logic('Category was not selected.');
        }

        return $this->categoryDictionary = $this->categoryDictionaryRepository->get($this->getTemplateCategoryId());
    }

    public function hasCategoryTemplate(): bool
    {
        return !empty($this->getData(ProductResource::COLUMN_TEMPLATE_CATEGORY_ID));
    }

    public function getTemplateCategoryId(): int
    {
        return (int)$this->getData(ProductResource::COLUMN_TEMPLATE_CATEGORY_ID);
    }

    public function getOnlineTitle(): string
    {
        return (string)$this->getData(ProductResource::COLUMN_ONLINE_TITLE);
    }

    public function getCurrencyCode(): string
    {
        return $this->getAccount()->getCurrencyCode();
    }

    // ---------------------------------------

    public function setOnlineQty(int $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_QTY, $value);

        return $this;
    }

    public function getOnlineMaxPrice(): ?float
    {
        return (float)$this->getData(ProductResource::COLUMN_ONLINE_MAX_PRICE);
    }

    public function setOnlineMaxPrice(?float $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_MAX_PRICE, $value);

        return $this;
    }

    public function getOnlineMinPrice(): ?float
    {
        return (float)$this->getData(ProductResource::COLUMN_ONLINE_MIN_PRICE);
    }

    public function setOnlineMinPrice(?float $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_MIN_PRICE, $value);

        return $this;
    }

    public function setOnlineCategoryId(int $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_CATEGORY_ID, $value);

        return $this;
    }

    public function setOnlineCategoryData(string $data): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_CATEGORIES_DATA, $data);

        return $this;
    }

    public function getOnlineCategoryData(): string
    {
        return (string)$this->getData(ProductResource::COLUMN_ONLINE_CATEGORIES_DATA);
    }

    public function getOnlineCategoryId(): ?int
    {
        $categoryId = $this->getData(ProductResource::COLUMN_ONLINE_CATEGORY_ID);
        if (empty($categoryId)) {
            return null;
        }

        return (int)$categoryId;
    }

    public function setOnlineShippingTemplateId(string $templateId): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_SHIPPING_TEMPLATE_ID, $templateId);

        return $this;
    }

    public function getOnlineShippingTemplateId(): ?string
    {
        return $this->getData(ProductResource::COLUMN_ONLINE_SHIPPING_TEMPLATE_ID);
    }

    public function setOnlinePreparationTime(int $time): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_PREPARATION_TIME, $time);

        return $this;
    }

    public function getOnlinePreparationTime(): ?int
    {
        $value = $this->getData(ProductResource::COLUMN_ONLINE_PREPARATION_TIME);

        return $value !== null ? (int)$value : null;
    }

    // ---------------------------------------

    public function changeListing(\M2E\Temu\Model\Listing $listing): self
    {
        $this->setListingId($listing->getId());
        $this->initListing($listing);

        return $this;
    }

    private function setListingId(int $listingId): self
    {
        $this->setData(ProductResource::COLUMN_LISTING_ID, $listingId);

        return $this;
    }

    private function setMagentoProductId(int $magentoProductId): self
    {
        $this->setData(ProductResource::COLUMN_MAGENTO_PRODUCT_ID, $magentoProductId);

        return $this;
    }

    public function setTemplateCategoryId(int $id): self
    {
        $this->setData(ProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $id);

        return $this;
    }

    private function setChannelProductId(string $productId): self
    {
        $this->setData(ProductResource::COLUMN_CHANNEL_PRODUCT_ID, $productId);

        return $this;
    }

    public function setOnlineTitle(string $onlineTitle): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_TITLE, $onlineTitle);

        return $this;
    }

    public function setOnlineDescription(string $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_DESCRIPTION, $value);

        return $this;
    }

    public function getOnlineDescription(): string
    {
        return (string)$this->getData(ProductResource::COLUMN_ONLINE_DESCRIPTION);
    }

    public function setOnlineImages(string $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_IMAGE, $value);

        return $this;
    }

    public function getOnlineImages(): ?string
    {
        $images = $this->getData(ProductResource::COLUMN_ONLINE_IMAGE);
        if (empty($images)) {
            return null;
        }

        return (string)$images;
    }

    public function setOnlineItemTaxCode(?string $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_ITEM_TAX_CODE, $value);

        return $this;
    }

    public function getOnlineItemTaxCode(): ?string
    {
        return $this->getData(ProductResource::COLUMN_ONLINE_ITEM_TAX_CODE);
    }

    public function setOnlineBulletPoints(?string $value): self
    {
        $this->setData(ProductResource::COLUMN_ONLINE_BULLET_POINTS, $value);

        return $this;
    }

    public function getOnlineBulletPoints(): ?string
    {
        return $this->getData(ProductResource::COLUMN_ONLINE_BULLET_POINTS);
    }

    // ----------------------------------------

    private function setStatusChanger(int $statusChanger): self
    {
        $this->validateStatusChanger($statusChanger);

        $this->setData(ProductResource::COLUMN_STATUS_CHANGER, $statusChanger);

        return $this;
    }

    // ---------------

    public static function getStatusTitle(int $status): string
    {
        $statuses = [
            self::STATUS_NOT_LISTED => (string)__('Not Listed'),
            self::STATUS_LISTED => (string)__('Active'),
            self::STATUS_BLOCKED => (string)__('Incomplete'),
            self::STATUS_INACTIVE => (string)__('Inactive'),
        ];

        return $statuses[$status] ?? 'Unknown';
    }

    // ----------------------------------------

    private function validateStatusChanger(int $changer): void
    {
        $allowed = [
            self::STATUS_CHANGER_SYNCH,
            self::STATUS_CHANGER_USER,
            self::STATUS_CHANGER_COMPONENT,
            self::STATUS_CHANGER_OBSERVER,
        ];

        if (!in_array($changer, $allowed)) {
            throw new \M2E\Temu\Model\Exception\Logic(sprintf('Status changer %s not valid.', $changer));
        }
    }

    private function getVariantSkuOnlineQtySum(): int
    {
        $result = 0;
        foreach ($this->getVariants() as $variant) {
            $result += $variant->getOnlineQty();
        }

        return $result;
    }

    private function getMinOnlinePriceFromVariantSku(): ?float
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

    private function getMaxOnlinePriceFromVariantSku(): ?float
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
            if ($variant->isStatusNotListed()) {
                continue;
            }

            $prices[] = $variant->getOnlinePrice();
        }

        return $prices;
    }

    private function loadVariants(): void
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->variants)) {
            return;
        }

        $variants = [];
        foreach ($this->productRepository->findVariantsByProduct($this) as $variant) {
            $variants[$variant->getMagentoProductId()] = $variant;
        }

        $this->variants = $variants;
    }

    public function getFirstVariant(): \M2E\Temu\Model\Product\VariantSku
    {
        $variants = $this->getVariants();

        return reset($variants);
    }

    public function addVariant(\M2E\Temu\Model\Product\VariantSku $variant): self
    {
        $this->loadVariants();

        if (count($this->variants) > 0 && $this->isSimple()) {
            throw new \M2E\Temu\Model\Exception\Logic(
                'Unable to init variant product',
                ['variant_sku' => $variant->getSku()],
            );
        }

        $this->variants[$variant->getMagentoProductId()] = $variant;

        return $this;
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function getVariants(): array
    {
        $this->loadVariants();

        return array_values($this->variants);
    }

    public function findVariantBySkuId(string $skuId): ?\M2E\Temu\Model\Product\VariantSku
    {
        foreach ($this->getVariants() as $variant) {
            if ($variant->getSkuId() === $skuId) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku\OnlineData[]
     */
    public function getVariantOnlineData(): array
    {
        $result = [];
        foreach ($this->getVariants() as $variant) {
            $result[] = $variant->getOnlineData();
        }

        return $result;
    }

    public function recalculateOnlineDataByVariants(): self
    {
        $this
            ->setOnlineQty($this->getVariantSkuOnlineQtySum())
            ->setOnlineMinPrice($this->getMinOnlinePriceFromVariantSku())
            ->setOnlineMaxPrice($this->getMaxOnlinePriceFromVariantSku());

        return $this;
    }

    public function hasBlockingByError(): bool
    {
        $rawDate = $this->getData(ProductResource::COLUMN_LAST_BLOCKING_ERROR_DATE);
        if (empty($rawDate)) {
            return false;
        }

        $lastBlockingDate = \M2E\Core\Helper\Date::createDateGmt($rawDate);
        $twentyFourHoursAgoDate = \M2E\Core\Helper\Date::createCurrentGmt()->modify('-24 hour');

        return $lastBlockingDate->getTimestamp() > $twentyFourHoursAgoDate->getTimestamp();
    }

    public function removeBlockingByError(): self
    {
        $this->setData(ProductResource::COLUMN_LAST_BLOCKING_ERROR_DATE, null);

        return $this;
    }

    public function setVariationAttributes(\M2E\Temu\Model\Product\Dto\VariationAttributes $variationAttributes): self
    {
        $this->setData(ProductResource::COLUMN_VARIATION_ATTRIBUTES, $variationAttributes->exportToJson());

        return $this;
    }

    public function getVariationAttributes(): \M2E\Temu\Model\Product\Dto\VariationAttributes
    {
        return (new \M2E\Temu\Model\Product\Dto\VariationAttributes())
            ->importFromJson((string)$this->getData(ProductResource::COLUMN_VARIATION_ATTRIBUTES));
    }
}
