<?php

namespace M2E\Temu\Model\Order;

use M2E\Temu\Model\ResourceModel\Order\Item as OrderItemResource;

class Item extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    private \M2E\Temu\Model\Order $order;
    private ?\M2E\Temu\Model\Magento\Product $magentoProduct = null;
    private ?\M2E\Temu\Model\Order\Item\ProxyObject $proxy = null;

    private \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory;

    // ----------------------------------------

    private ?\M2E\Temu\Model\Product $listingProduct = null;
    private \M2E\Temu\Model\Product\VariantSku $variantSku;
    private \M2E\Temu\Model\Order\Item\ProxyObjectFactory $proxyObjectFactory;
    private \M2E\Core\Helper\Magento\Store $magentoStoreHelper;
    private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\Temu\Model\Order\Item\OptionsFinder $optionsFinder;
    private \M2E\Temu\Model\Product\Repository $listingProductRepository;
    private \M2E\Temu\Model\Order\Item\ProductAssignService $productAssignService;
    /** @var \M2E\Temu\Model\Order\Repository */
    private Repository $orderRepository;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository;

    public function __construct(
        \M2E\Temu\Model\Order\Repository $orderRepository,
        \M2E\Temu\Model\Order\Item\ProductAssignService $productAssignService,
        \M2E\Temu\Model\Product\Repository $listingProductRepository,
        \M2E\Temu\Model\Order\Item\OptionsFinder $optionsFinder,
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\Core\Helper\Magento\Store $magentoStoreHelper,
        \M2E\Temu\Model\Order\Item\ProxyObjectFactory $proxyObjectFactory,
        \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory,
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
        $this->magentoProductFactory = $magentoProductFactory;
        $this->proxyObjectFactory = $proxyObjectFactory;
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->optionsFinder = $optionsFinder;
        $this->listingProductRepository = $listingProductRepository;
        $this->productAssignService = $productAssignService;
        $this->orderRepository = $orderRepository;
        $this->unmanagedProductRepository = $unmanagedProductRepository;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(OrderItemResource::class);
    }

    // ----------------------------------------

    public function create(
        \M2E\Temu\Model\Order $order,
        string $channelOrderItemId,
        string $channelProductId,
        ?string $productSku,
        int $qty
    ): self {
        $this->setData(OrderItemResource::COLUMN_ORDER_ID, $order->getId())
             ->setData(OrderItemResource::COLUMN_CHANNEL_ORDER_ITEM_ID, $channelOrderItemId)
             ->setData(OrderItemResource::COLUMN_CHANNEL_PRODUCT_ID, $channelProductId)
             ->setData(OrderItemResource::COLUMN_QTY, $qty);

        if ($productSku !== null) {
            $this->setData(OrderItemResource::COLUMN_PRODUCT_SKU, $productSku);
        }

        $this->initOrder($order);

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->validateStatus($status);
        $this->setData(OrderItemResource::COLUMN_ORDER_ITEM_STATUS, $status);

        return $this;
    }

    public function getStatus(): int
    {
        return (int)($this->getData(OrderItemResource::COLUMN_ORDER_ITEM_STATUS) ?? 0);
    }

    public function isStatusCanceled(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Order::STATUS_CANCELED;
    }

    public function isStatusUnshipped(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Order::STATUS_UNSHIPPED;
    }

    public function isStatusShipped(): bool
    {
        return $this->getStatus() === \M2E\Temu\Model\Order::STATUS_SHIPPED;
    }

    private function validateStatus(int $status): void
    {
        if (!in_array($status, \M2E\Temu\Model\Order::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid order status $status.");
        }
    }

    public function getOrderId(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_ORDER_ID);
    }

    public function getChanelOrderItemId(): string
    {
        return $this->getData(OrderItemResource::COLUMN_CHANNEL_ORDER_ITEM_ID);
    }

    public function getSkuId(): string
    {
        return $this->getData(OrderItemResource::COLUMN_PRODUCT_SKU_ID);
    }

    public function setSkuId(string $skuId): self
    {
        $this->setData(OrderItemResource::COLUMN_PRODUCT_SKU_ID, $skuId);

        return $this;
    }

    public function getMagentoProductId(): ?int
    {
        $productId = $this->getData(OrderItemResource::COLUMN_MAGENTO_PRODUCT_ID);
        if ($productId === null) {
            return null;
        }

        return (int)$productId;
    }

    public function getQtyReserved(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_QTY_RESERVED);
    }

    //region Column product_details
    public function setAssociatedOptions(array $options): self
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_options',
            $options
        );

        return $this;
    }

    public function getAssociatedOptions()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_options',
            []
        );
    }

    public function removeAssociatedOptions(): void
    {
        $this->setAssociatedOptions([]);
    }

    public function setAssociatedProducts(array $products): Item
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_products',
            $products
        );

        return $this;
    }

    public function getAssociatedProducts()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_products',
            []
        );
    }

    public function removeAssociatedProducts(): void
    {
        $this->setAssociatedProducts([]);
    }

    public function setReservedProducts(array $products): Item
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'reserved_products',
            $products
        );

        return $this;
    }

    public function getReservedProducts()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'reserved_products',
            []
        );
    }
    //endregion

    //region Order
    public function setOrder(\M2E\Temu\Model\Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function initOrder(\M2E\Temu\Model\Order $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): \M2E\Temu\Model\Order
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->order)) {
            return $this->order;
        }

        return $this->order = $this->orderRepository->get($this->getOrderId());
    }
    //endregion

    //########################################

    public function setProduct($product): self
    {
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $this->magentoProduct = null;

            return $this;
        }

        if ($this->magentoProduct === null) {
            $this->magentoProduct = $this->magentoProductFactory->create();
        }
        $this->magentoProduct->setProduct($product);

        return $this;
    }

    /**
     * @throws \M2E\Temu\Model\Exception
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getProduct(): ?\Magento\Catalog\Model\Product
    {
        if ($this->getMagentoProductId() === null) {
            return null;
        }

        if (!$this->isMagentoProductExists()) {
            return null;
        }

        return $this->getMagentoProduct()->getProduct();
    }

    public function getMagentoProduct(): ?\M2E\Temu\Model\Magento\Product
    {
        if ($this->getMagentoProductId() === null) {
            return null;
        }

        if ($this->magentoProduct === null) {
            $this->magentoProduct = $this->magentoProductFactory->createByProductId((int)$this->getMagentoProductId());
            $this->magentoProduct->setStoreId($this->getOrder()->getStoreId());
        }

        return $this->magentoProduct;
    }

    public function getStoreId(): int
    {
        $variantSku = $this->getVariantSku();

        if ($variantSku === null) {
            return $this->getOrder()->getStoreId();
        }

        $storeId = $variantSku->getListing()->getStoreId();

        if ($storeId !== \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $storeId;
        }

        if ($this->getMagentoProductId() === null) {
            return $this->magentoStoreHelper->getDefaultStoreId();
        }

        $storeIds = $this
            ->magentoProductFactory
            ->createByProductId((int)$this->getMagentoProductId())
            ->getStoreIds();

        if (empty($storeIds)) {
            return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        return (int)array_shift($storeIds);
    }

    //########################################

    /**
     * Associate order item with product in magento
     * @throws \M2E\Temu\Model\Exception
     * @throws \Exception
     */
    public function associateWithProduct(): void
    {
        if (
            $this->getMagentoProductId() === null
            || !$this->getMagentoProduct()->exists()
        ) {
            $this->productAssignService->assign(
                $this,
                $this->getAssociatedProduct(),
                \M2E\Core\Helper\Data::INITIATOR_EXTENSION
            );
        }

        $supportedProductTypes = [
            \M2E\Temu\Helper\Magento\Product::TYPE_SIMPLE,
        ];

        if (!in_array($this->getMagentoProduct()->getTypeId(), $supportedProductTypes)) {
            $message = \M2E\Temu\Helper\Module\Log::encodeDescription(
                'Order Import does not support Product type: %type%.',
                [
                    'type' => $this->getMagentoProduct()->getTypeId(),
                ],
            );

            throw new \M2E\Temu\Model\Exception($message);
        }

        $this->associateVariationWithOptions();

        if (!$this->getMagentoProduct()->isStatusEnabled()) {
            throw new \M2E\Temu\Model\Exception('Product is disabled.');
        }
    }

    //########################################

    /**
     * Associate order item variation with options of magento product
     * @throws \LogicException
     * @throws \Exception
     */
    private function associateVariationWithOptions(): void
    {
        $magentoProduct = $this->getMagentoProduct();
        if ($magentoProduct === null) {
            return;
        }

        $existOptions = $this->getAssociatedOptions();
        $existProducts = $this->getAssociatedProducts();

        if (
            count($existProducts) == 1
            && ($magentoProduct->isDownloadableType()
                || $magentoProduct->isGroupedType()
                || $magentoProduct->isConfigurableType())
        ) {
            // grouped and configurable products can have only one associated product mapped with sold variation
            // so if count($existProducts) == 1 - there is no need for further actions
            return;
        }

        $productDetails = $this->getAssociatedProductDetails($magentoProduct);

        if (!isset($productDetails['associated_options'])) {
            return;
        }

        $existOptionsIds = array_keys($existOptions);
        $foundOptionsIds = array_keys($productDetails['associated_options']);

        if (empty($existOptions) && empty($existProducts)) {
            // options mapping invoked for the first time, use found options
            $this->setAssociatedOptions($productDetails['associated_options']);

            if (isset($productDetails['associated_products'])) {
                $this->setAssociatedProducts($productDetails['associated_products']);
            }

            $this->save();

            return;
        }

        if (!empty(array_diff($foundOptionsIds, $existOptionsIds))) {
            // options were already mapped, but not all of them
            throw new \M2E\Temu\Model\Exception\Logic('Selected Options do not match the Product Options.');
        }
    }

    /**
     * @throws \M2E\Temu\Model\Exception
     */
    private function getAssociatedProductDetails(\M2E\Temu\Model\Magento\Product $magentoProduct): array
    {
        if (!$magentoProduct->getTypeId()) {
            return [];
        }

        $magentoOptions = $this
            ->prepareMagentoOptions($magentoProduct->getVariationInstance()->getVariationsTypeRaw());

        $optionsFinder = $this->optionsFinder;
        $optionsFinder->setProduct($magentoProduct)
                      ->setMagentoOptions($magentoOptions)
                      ->addChannelOptions();

        $optionsFinder->find();

        if (!$optionsFinder->hasFailedOptions()) {
            return $optionsFinder->getOptionsData();
        }

        throw new \M2E\Temu\Model\Exception($optionsFinder->getOptionsNotFoundMessage());
    }

    //########################################

    public function assignProduct($productId): void
    {
        $magentoProduct = $this->magentoProductFactory->createByProductId((int)$productId);

        if (!$magentoProduct->exists()) {
            $this->setData('product_id');
            $this->setAssociatedProducts([]);
            $this->setAssociatedOptions([]);
            $this->save();

            throw new \InvalidArgumentException('Product does not exist.');
        }

        $this->setMagentoProductId((int)$productId);

        $this->save();
    }

    public function setMagentoProductId(int $productId)
    {
        $this->setData(OrderItemResource::COLUMN_MAGENTO_PRODUCT_ID, $productId);
    }

    public function removeMagentoProductId(): void
    {
        $this->setData(OrderItemResource::COLUMN_MAGENTO_PRODUCT_ID, null);
    }

    //########################################

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function unassignProduct()
    {
        $this->setData('product_id');
        $this->setAssociatedProducts([]);
        $this->setAssociatedOptions([]);

        if ($this->getOrder()->getReserve()->isPlaced()) {
            $this->getOrder()->getReserve()->cancel();
            $this->getOrder()->getReserve()->addSuccessLogCancelQty();
        }

        $this->save();
    }

    //########################################

    public function pretendedToBeSimple(): bool
    {
        return false;
    }

    //########################################

    public function getAdditionalData(): array
    {
        $value = $this->getData('additional_data');
        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }

    public function isMagentoProductExists(): bool
    {
        return $this->magentoProductFactory->createByProductId((int)$this->getMagentoProductId())->exists();
    }

    /**
     * @return \M2E\Temu\Model\Order\Item\ProxyObject
     */
    public function getProxy(): \M2E\Temu\Model\Order\Item\ProxyObject
    {
        if ($this->proxy === null) {
            $this->proxy = $this->proxyObjectFactory->create($this);
        }

        return $this->proxy;
    }

    // ----------------------------------------

    public function getAccount(): \M2E\Temu\Model\Account
    {
        return $this->getOrder()->getAccount();
    }

    // ----------------------------------------

    public function getVariantSku(): ?\M2E\Temu\Model\Product\VariantSku
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->variantSku)) {
            return $this->variantSku;
        }

        $variantSku = $this->listingProductRepository->findVariantSkuByChannelProductIdAndSkuId(
            $this->getChannelProductId(),
            $this->getSkuId(),
        );

        if ($variantSku === null) {
            return null;
        }

        return $this->variantSku = $variantSku;
    }

    // ----------------------------------------

    public function getChannelProductId(): string
    {
        return $this->getData(OrderItemResource::COLUMN_CHANNEL_PRODUCT_ID);
    }

    public function getProductSku(): ?string
    {
        return $this->getData(OrderItemResource::COLUMN_PRODUCT_SKU);
    }

    public function setSalePrice(float $price): self
    {
        $this->setData(OrderItemResource::COLUMN_SALE_PRICE, $price);

        return $this;
    }

    public function getSalePrice(): float
    {
        return (float)$this->getData(OrderItemResource::COLUMN_SALE_PRICE);
    }

    public function setBasePrice(float $price): self
    {
        $this->setData(OrderItemResource::COLUMN_BASE_PRICE, $price);

        return $this;
    }

    public function getBasePrice(): float
    {
        return (float)$this->getData(OrderItemResource::COLUMN_BASE_PRICE);
    }

    public function getQty(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_QTY);
    }

    public function setQtyReserved(int $qty): self
    {
        $this->setData(OrderItemResource::COLUMN_QTY_RESERVED, $qty);

        return $this;
    }

    public function setQtyCancelledBeforeShipment(int $qtyCancelledBeforeShipment): self
    {
        $this->setData(OrderItemResource::COLUMN_QTY_CANCELLED_BEFORE_SHIPMENT, $qtyCancelledBeforeShipment);

        return $this;
    }

    public function getQtyCancelledBeforeShipment(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_QTY_CANCELLED_BEFORE_SHIPMENT);
    }

    public function setFulfillmentType(int $fulfillmentType): self
    {
        $this->setData(OrderItemResource::COLUMN_FULFILLMENT_TYPE, $fulfillmentType);

        return $this;
    }

    public function getFulfillmentType(): int
    {
        return $this->getData(OrderItemResource::COLUMN_FULFILLMENT_TYPE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasVariation(): bool
    {
        return false;
    }

    public function setTrackingDetails(?array $details): self
    {
        $this->setData(OrderItemResource::COLUMN_TRACKING_DETAILS, json_encode($details ?? []));

        return $this;
    }

    public function getTrackingDetails(): array
    {
        $trackingDetails = $this->getData(OrderItemResource::COLUMN_TRACKING_DETAILS);
        if (empty($trackingDetails)) {
            return [];
        }

        return json_decode($trackingDetails, true);
    }

    public function canCreateMagentoOrder(): bool
    {
        return $this->isOrdersCreationEnabled();
    }

    public function isReservable(): bool
    {
        return $this->isOrdersCreationEnabled();
    }

    protected function isOrdersCreationEnabled(): bool
    {
        $variantSku = $this->getVariantSku();
        if ($variantSku === null) {
            return $this->getAccount()->getOrdersSettings()->isUnmanagedListingEnabled();
        }

        return $this->getAccount()->getOrdersSettings()->isListingEnabled();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     * @throws \M2E\Temu\Model\Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getAssociatedProduct(): \Magento\Catalog\Model\Product
    {
        // Item was listed by M2E
        // ---------------------------------------
        if ($this->getVariantSku() !== null) {
            return $this->getVariantSku()->getMagentoProduct()->getProduct();
        }

        // Unmanaged Item
        // ---------------------------------------
        $sku = $this->getProductSku();

        if (
            $sku != ''
            && strlen($sku) <= \M2E\Temu\Helper\Magento\Product::SKU_MAX_LENGTH
        ) {
            $collection = $this->magentoProductCollectionFactory->create();
            $collection->setStoreId($this->getOrder()->getAssociatedStoreId());
            $collection->addAttributeToSelect('sku');
            $collection->addAttributeToFilter('sku', $sku);

            /** @var \Magento\Catalog\Model\Product $foundedProduct */
            $foundedProduct = $collection->getFirstItem();

            if (!$foundedProduct->isObjectNew()) {
                $this->associateWithProductEvent($foundedProduct);

                return $foundedProduct;
            }
        }

        // Unmanaged Item and linked
        // ---------------------------------------
        $skuId = $this->getSkuId();
        $unmanagedProductVariant = $this->unmanagedProductRepository->findVariantBySkuIdAndAccountId($skuId, $this->getAccount()->getId());

        if ($unmanagedProductVariant !== null && $unmanagedProductVariant->getMagentoProductId() !== 0) {
            return $unmanagedProductVariant->getMagentoProduct()->getProduct();
        }

        // Create new Product in Magento
        // ---------------------------------------
        $newProduct = $this->createProduct();
        $this->associateWithProductEvent($newProduct);

        return $newProduct;
    }

    public function prepareMagentoOptions($options): array
    {
        return \M2E\Temu\Helper\Component\Temu::prepareOptionsForOrders($options);
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \M2E\Temu\Model\Order\Exception\ProductCreationDisabled
     */
    protected function createProduct(): \Magento\Catalog\Model\Product
    {
        throw new \M2E\Temu\Model\Order\Exception\ProductCreationDisabled(
            (string)__('The product associated with this order could not be found in the Magento catalog.'),
        );
    }

    protected function associateWithProductEvent(\Magento\Catalog\Model\Product $product)
    {
        if (!$this->hasVariation()) {
            $this->_eventManager->dispatch('m2e_temu_associate_order_item_to_product', [
                'product' => $product,
                'order_item' => $this,
            ]);
        }
    }

    public function setOriginalPrice(float $price): self
    {
        $this->setData(OrderItemResource::COLUMN_ORIGINAL_PRICE, $price);

        return $this;
    }

    public function getOriginalPrice(): float
    {
        return (float)$this->getData(OrderItemResource::COLUMN_ORIGINAL_PRICE);
    }
}
