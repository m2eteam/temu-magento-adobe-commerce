<?php

namespace M2E\Temu\Model;

use M2E\Temu\Model\Log\AbstractModel as Log;
use M2E\Temu\Model\ResourceModel\Order as OrderResource;

class Order extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public const ADDITIONAL_DATA_KEY_IN_ORDER = 'm2e_temu_order';

    public const MAGENTO_ORDER_CREATION_FAILED_YES = 1;
    public const MAGENTO_ORDER_CREATION_FAILED_NO = 0;

    public const MAGENTO_ORDER_CREATE_MAX_TRIES = 3;

    public const STATUS_UNKNOWN = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_UNSHIPPED = 2;
    public const STATUS_SHIPPED = 3;
    public const STATUS_CANCELED = 4;
    public const STATUS_REFUNDED = 5;
    public const STATUS_PARTIALLY_SHIPPED = 6;

    public const STATUSES = [
        self::STATUS_UNKNOWN,
        self::STATUS_PENDING,
        self::STATUS_UNSHIPPED,
        self::STATUS_SHIPPED,
        self::STATUS_CANCELED,
        self::STATUS_REFUNDED,
        self::STATUS_PARTIALLY_SHIPPED,
    ];

    private bool $statusUpdateRequired = false;

    /** @var \M2E\Temu\Model\Order\Item[] */
    private array $items;
    private ?\Magento\Sales\Model\Order $magentoOrder = null;
    private Order\ShippingAddress $shippingAddress;
    private Account $account;
    private Order\ProxyObject $proxy;
    private Order\Reserve $reserve;
    private \M2E\Temu\Model\Order\Log\Service $logService;

    // ----------------------------------------

    private \M2E\Temu\Model\Magento\Quote\Manager $quoteManager;
    private \M2E\Temu\Model\Magento\Quote\BuilderFactory $magentoQuoteBuilderFactory;
    private \M2E\Temu\Model\Magento\Order\Updater $magentoOrderUpdater;
    private \Magento\Store\Model\StoreManager $storeManager;
    private \Magento\Sales\Model\OrderFactory $orderFactory;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Magento\Catalog\Helper\Product $productHelper;
    private \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender;
    private \M2E\Temu\Model\Order\ProxyObjectFactory $proxyObjectFactory;
    private Order\ShippingAddressFactory $shippingAddressFactory;
    private \M2E\Temu\Model\Order\Log\ServiceFactory $orderLogServiceFactory;
    private \M2E\Temu\Model\Order\ReserveFactory $orderReserveFactory;
    private \M2E\Temu\Helper\Module\Exception $exceptionHelper;
    private \M2E\Temu\Helper\Module\Logger $loggerHelper;
    private \M2E\Temu\Helper\Data\GlobalData $globalDataHelper;
    private \M2E\Core\Helper\Magento\Store $magentoStoreHelper;
    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private Order\Item\Repository $orderItemRepository;
    private \M2E\Temu\Model\Order\EventDispatcher $orderEventDispatcher;
    private \M2E\Temu\Model\Order\Repository $orderRepository;
    private \M2E\Temu\Model\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory;

    public function __construct(
        \M2E\Temu\Model\Order\Repository $orderRepository,
        \M2E\Temu\Model\Order\EventDispatcher $orderEventDispatcher,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\Magento\Quote\Manager $quoteManager,
        \M2E\Temu\Model\Magento\Quote\BuilderFactory $magentoQuoteBuilderFactory,
        \M2E\Temu\Model\Magento\Order\Updater $magentoOrderUpdater,
        \M2E\Temu\Model\Order\ReserveFactory $orderReserveFactory,
        \M2E\Temu\Model\Order\Log\ServiceFactory $orderLogServiceFactory,
        \M2E\Temu\Model\Order\ProxyObjectFactory $proxyObjectFactory,
        Order\ShippingAddressFactory $shippingAddressFactory,
        \M2E\Temu\Model\Order\Item\Repository $orderItemRepository,
        \M2E\Core\Helper\Magento\Store $magentoStoreHelper,
        \M2E\Temu\Helper\Data\GlobalData $globalDataHelper,
        \M2E\Temu\Helper\Module\Logger $loggerHelper,
        \M2E\Temu\Helper\Module\Exception $exceptionHelper,
        \M2E\Temu\Model\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Helper\Product $productHelper,
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
            $data
        );

        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productHelper = $productHelper;
        $this->quoteManager = $quoteManager;
        $this->proxyObjectFactory = $proxyObjectFactory;
        $this->shippingAddressFactory = $shippingAddressFactory;
        $this->orderLogServiceFactory = $orderLogServiceFactory;
        $this->orderReserveFactory = $orderReserveFactory;
        $this->exceptionHelper = $exceptionHelper;
        $this->loggerHelper = $loggerHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->magentoQuoteBuilderFactory = $magentoQuoteBuilderFactory;
        $this->magentoOrderUpdater = $magentoOrderUpdater;
        $this->accountRepository = $accountRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderEventDispatcher = $orderEventDispatcher;
        $this->orderSender = $orderSender;
        $this->orderRepository = $orderRepository;
        $this->priceTaxRateFactory = $priceTaxRateFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(OrderResource::class);
    }

    // ----------------------------------------

    public static function getStatusTitle(int $status): string
    {
        $statuses = [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_UNSHIPPED => __('Unshipped'),
            self::STATUS_SHIPPED => __('Shipped'),
            self::STATUS_PARTIALLY_SHIPPED => __('Partially Shipped'),
            self::STATUS_CANCELED => __('Canceled'),
            self::STATUS_REFUNDED => __('Refunded'),
            self::STATUS_UNKNOWN => __('Unknown'),
        ];

        return (string)($statuses[$status] ?? __('Unknown'));
    }

    // ----------------------------------------

    public function create(
        Account $account,
        string $orderId,
        \DateTimeInterface $purchaseDate,
        string $currency
    ): self {
        $this->setData(OrderResource::COLUMN_ACCOUNT_ID, $account->getId())
             ->setData(OrderResource::COLUMN_CHANNEL_ORDER_ID, $orderId)
             ->setData(OrderResource::COLUMN_PURCHASE_DATE, $purchaseDate->format('Y-m-d H:i:s'))
            ->setData(OrderResource::COLUMN_CURRENCY, $currency);

        $this->initAccount($account);

        return $this;
    }

    // ----------------------------------------

    public function resetItems(): void
    {
        unset($this->items);
    }

    public function getItems(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->items)) {
            return $this->items;
        }

        return $this->items = $this->orderItemRepository->findByOrder($this);
    }

    public function getItem(int $itemId): \M2E\Temu\Model\Order\Item
    {
        $item = $this->findItem($itemId);
        if ($item === null) {
            throw new \LogicException("Order item with id '$itemId' does not exist");
        }

        return $item;
    }

    public function findItem(int $itemId): ?\M2E\Temu\Model\Order\Item
    {
        foreach ($this->getItems() as $item) {
            if ($item->getId() === $itemId) {
                return $item;
            }
        }

        return null;
    }

    public function getMagentoOrderCreationLatestAttemptDate()
    {
        return $this->getData(OrderResource::COLUMN_MAGENTO_ORDER_CREATION_LATEST_ATTEMPT_DATE);
    }

    public function getCreateDate()
    {
        return $this->getData(OrderResource::COLUMN_CREATE_DATE);
    }

    public function getReservationState(): int
    {
        return (int)$this->getData(OrderResource::COLUMN_RESERVATION_STATE);
    }

    public function getReservationStartDate(): string
    {
        return (string)$this->getData(OrderResource::COLUMN_RESERVATION_START_DATE);
    }

    public function getChannelOrderId(): string
    {
        return (string)$this->getData(OrderResource::COLUMN_CHANNEL_ORDER_ID);
    }

    public function setChannelOrderId(string $temuOrderId): self
    {
        $this->setData(OrderResource::COLUMN_CHANNEL_ORDER_ID, $temuOrderId);

        return $this;
    }

    /**
     * Check whether the order has items, listed by M2E (also true for linked Unmanaged listings)
     */
    public function hasListingProductItems(): bool
    {
        return !empty($this->getListingProductVariantSkus());
    }

    /**
     * @return \M2E\Temu\Model\Product\VariantSku[]
     */
    public function getListingProductVariantSkus(): array
    {
        $listingProducts = [];
        foreach ($this->getItems() as $item) {
            $variantSku = $item->getVariantSku();

            if ($variantSku === null) {
                continue;
            }

            $listingProducts[] = $variantSku;
        }

        return $listingProducts;
    }

    /**
     * Check whether the order has items, listed by Unmanaged software
     */
    public function hasOtherListingItems(): bool
    {
        return count($this->getListingProductVariantSkus()) !== count($this->getItems());
    }

    public function isMagentoShipmentCreatedByOrder(\Magento\Sales\Model\Order\Shipment $magentoShipment): bool
    {
        $additionalData = $this->getAdditionalData();
        if (empty($additionalData['created_shipments_ids']) || !is_array($additionalData['created_shipments_ids'])) {
            return false;
        }

        return in_array($magentoShipment->getId(), $additionalData['created_shipments_ids']);
    }

    public function getAdditionalData(): array
    {
        $value = $this->getData(OrderResource::COLUMN_ADDITIONAL_DATA);
        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }

    // ----------------------------------------

    public function canCreateMagentoOrder(): bool
    {
        if ($this->getMagentoOrderId() !== null) {
            return false;
        }

        if ($this->isCanceled()) {
            return false;
        }

        if ($this->isStatusPending()) {
            return false;
        }

        foreach ($this->getItems() as $item) {
            if (!$item->canCreateMagentoOrder()) {
                return false;
            }
        }

        if ($this->getBuyerName() === null) {
            return false;
        }

        if ($this->getBuyerEmail() === null) {
            return false;
        }

        if ($this->getShippingDetails() === []) {
            return false;
        }

        if (!$this->isAllShippingDetailsExit()) {
            return false;
        }

        $allItemsCanceled = true;
        foreach ($this->getItems() as $item) {
            if (!$item->isStatusCanceled()) {
                $allItemsCanceled = false;
                break;
            }
        }

        if ($allItemsCanceled) {
            return false;
        }

        return true;
    }

    private function isAllShippingDetailsExit(): bool
    {
        $shippingDetails = $this->getShippingDetails();

        return isset(
            $shippingDetails['address']['recipient_name'],
            $shippingDetails['address']['street'],
            $shippingDetails['address']['country_code']
        );
    }

    //########################################

    public function hasMagentoOrder(): bool
    {
        return $this->getMagentoOrderId() !== null;
    }

    public function getMagentoOrderId()
    {
        return $this->getData(OrderResource::COLUMN_MAGENTO_ORDER_ID);
    }

    //########################################

    public function isCanceled(): bool
    {
        return $this->getStatus() === self::STATUS_CANCELED;
    }

    //region Order status
    public function setStatus(int $status): self
    {
        $this->validateStatus($status);
        $this->setData(OrderResource::COLUMN_ORDER_STATUS, $status);

        return $this;
    }

    private function validateStatus(int $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid order status $status.");
        }
    }

    public function getStatus(): int
    {
        return (int)($this->getData(OrderResource::COLUMN_ORDER_STATUS) ?? 0);
    }

    public function isStatusPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    public function isStatusCanceled(): bool
    {
        return $this->getStatus() === self::STATUS_CANCELED;
    }

    public function isStatusShipping(): bool
    {
        return $this->getStatus() === self::STATUS_SHIPPED;
    }

    public function isStatusPartiallyShipped(): bool
    {
        return $this->getStatus() === self::STATUS_PARTIALLY_SHIPPED;
    }

    public function isStatusUnshipping(): bool
    {
        return $this->getStatus() === self::STATUS_UNSHIPPED;
    }

    public function getStatusForMagentoOrder(): string
    {
        if ($this->isStatusUnshipping()) {
            return $this->getAccount()->getOrdersSettings()->getStatusMappingForProcessing();
        }

        if ($this->isStatusShipping()) {
            return $this->getAccount()->getOrdersSettings()->getStatusMappingForProcessingShipped();
        }

        return '';
    }
    //endregion

    // ---------------------------------------

    /**
     * @throws \Throwable
     * @throws \M2E\Temu\Model\Exception\Logic
     * @throws \M2E\Temu\Model\Magento\Quote\FailDuringEventProcessing
     * @throws \M2E\Temu\Model\Order\Exception\ProductCreationDisabled
     * @throws \M2E\Temu\Model\Exception
     */
    public function createMagentoOrder(): void
    {
        try {
            // Check if we are wrapped by an another MySql transaction
            // ---------------------------------------
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {
                $this->loggerHelper->process(
                    ['transaction_level' => $transactionLevel],
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }
            // ---------------------------------------

            /**
             *  Since version 2.1.8 Magento added check if product is saleable before creating quote.
             *  When order is creating from back-end, this check is skipped. See example at
             *  Magento\Sales\Controller\Adminhtml\Order\Create.php
             */
            $this->productHelper->setSkipSaleableCheck(true);

            // Store must be initialized before products
            // ---------------------------------------
            $this->associateWithStore();
            $this->associateItemsWithProducts();
            // ---------------------------------------

            $this->beforeCreateMagentoOrder();

            // Create magento order
            // ---------------------------------------
            $proxyOrder = $this->getProxy();
            $proxyOrder->setStore($this->getStore());

            $magentoQuoteBuilder = $this->magentoQuoteBuilderFactory->create($proxyOrder);
            $magentoQuote = $magentoQuoteBuilder->build();

            $this->globalDataHelper->unsetValue(self::ADDITIONAL_DATA_KEY_IN_ORDER);
            $this->globalDataHelper->setValue(self::ADDITIONAL_DATA_KEY_IN_ORDER, $this);

            try {
                $this->magentoOrder = $this->quoteManager->submit($magentoQuote);
            } catch (\M2E\Temu\Model\Magento\Quote\FailDuringEventProcessing $e) {
                $this->addWarningLog(
                    'Magento Order was created.
                     However one or more post-processing actions on Magento Order failed.
                     This may lead to some issues in the future.
                     Please check the configuration of the ancillary services of your Magento.
                     For more details, read the original Magento warning: %msg%.',
                    [
                        'msg' => $e->getMessage(),
                    ]
                );
                $this->magentoOrder = $e->getOrder();
            }

            $magentoOrderId = $this->getMagentoOrderId();

            if (empty($magentoOrderId)) {
                $now = \M2E\Core\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
                $this->addData([
                    'magento_order_id' => $this->magentoOrder->getId(),
                    'magento_order_creation_failure' => self::MAGENTO_ORDER_CREATION_FAILED_NO,
                    'magento_order_creation_latest_attempt_date' => $now,
                ]);

                $this->setMagentoOrder($this->magentoOrder);
                $this->orderRepository->save($this);
            }

            $this->afterCreateMagentoOrder();
            unset($magentoQuoteBuilder);
        } catch (\Throwable $exception) {
            unset($magentoQuoteBuilder);
            $this->globalDataHelper->unsetValue(self::ADDITIONAL_DATA_KEY_IN_ORDER);

            /**
             * \Magento\CatalogInventory\Model\StockManagement::registerProductsSale()
             * could open an transaction and may does not
             * close it in case of Exception. So all the next changes may be lost.
             */
            $connection = $this->resourceConnection->getConnection();
            if ($transactionLevel = $connection->getTransactionLevel()) {
                $this->loggerHelper->process(
                    [
                        'transaction_level' => $transactionLevel,
                        'error' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ],
                    'MySql Transaction Level Problem'
                );

                while ($connection->getTransactionLevel()) {
                    $connection->rollBack();
                }
            }

            $this->_eventManager->dispatch('m2e_temu_order_place_failure', ['order' => $this]);

            // ----------------------------------------
            $this->markMagentoOrderCreationFailure();
            $this->orderRepository->save($this);
            // ----------------------------------------

            $message = 'Magento Order was not created. Reason: %msg%';
            if ($exception instanceof \M2E\Temu\Model\Order\Exception\ProductCreationDisabled) {
                $this->addInfoLog($message, ['msg' => $exception->getMessage()], [], true);
            } else {
                $this->exceptionHelper->process($exception);
                $this->addErrorLog($message, ['msg' => $exception->getMessage()]);
            }

            if ($this->isReservable()) {
                $this->getReserve()->place();
            }

            throw $exception;
        }
    }

    // ---------------------------------------

    /**
     * Find the store, where order should be placed
     * @throws \M2E\Temu\Model\Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function associateWithStore()
    {
        $storeId = $this->getStoreId() ? $this->getStoreId() : $this->getAssociatedStoreId();
        $store = $this->storeManager->getStore($storeId);

        if ($store->getId() === null) {
            throw new \M2E\Temu\Model\Exception('Store does not exist.');
        }

        if ($this->getStoreId() != $store->getId()) {
            $this->setData(OrderResource::COLUMN_STORE_ID, $store->getId());
            $this->orderRepository->save($this);
        }

        if (!$store->getConfig('payment/temupayment/active')) {
            throw new \M2E\Temu\Model\Exception(
                strtr(
                    'Payment method "extension_title Payment" is disabled under
                <i>Stores > Settings > Configuration > Sales > Payment Methods > extension_title Payment.</i>',
                    [
                        'extension_title' => \M2E\Temu\Helper\Module::getExtensionTitle(),
                    ]
                )
            );
        }

        if (!$store->getConfig('carriers/temushipping/active')) {
            throw new \M2E\Temu\Model\Exception(
                strtr(
                    'Shipping method "extension_title Shipping" is disabled under
                <i>Stores > Settings > Configuration > Sales > Shipping Methods > extension_title Shipping.</i>',
                    [
                        'extension_title' => \M2E\Temu\Helper\Module::getExtensionTitle(),
                    ]
                )
            );
        }
    }

    public function getStoreId(): int
    {
        return (int)$this->getData('store_id');
    }

    //########################################

    public function getAssociatedStoreId(): ?int
    {
        $productVariantSkus = $this->getListingProductVariantSkus();

        if (empty($productVariantSkus)) {
            $storeId = $this->getAccount()->getOrdersSettings()->getUnmanagedListingStoreId();
        } else {
            $firstProductVariantSku = reset($productVariantSkus);
            if ($this->getAccount()->getOrdersSettings()->isListingStoreModeCustom()) {
                $storeId = $this->getAccount()->getOrdersSettings()->getListingStoreIdForCustomMode();
            } else {
                $storeId = $firstProductVariantSku->getListing()->getStoreId();
            }
        }

        if ($storeId == 0) {
            $storeId = $this->magentoStoreHelper->getDefaultStoreId();
        }

        return $storeId;
    }

    public function initAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): Account
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->account)) {
            $this->account = $this->accountRepository->get($this->getAccountId());
        }

        return $this->account;
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(OrderResource::COLUMN_ACCOUNT_ID);
    }

    public function setSiteId(int $siteId): self
    {
        $this->setData(OrderResource::COLUMN_SITE_ID, $siteId);

        return $this;
    }

    public function setRegionId(int $regionId): self
    {
        $this->setData(OrderResource::COLUMN_REGION_ID, $regionId);

        return $this;
    }

    public function getRegionId(): int
    {
        return (int)$this->getData(OrderResource::COLUMN_REGION_ID);
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore(): \Magento\Store\Api\Data\StoreInterface
    {
        return $this->storeManager->getStore($this->getStoreId());
    }

    /**
     * Associate each order item with product in magento
     */
    public function associateItemsWithProducts(): void
    {
        foreach ($this->getItems() as $item) {
            $item->associateWithProduct();
        }
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     * @throws \M2E\Temu\Model\Exception
     */
    private function beforeCreateMagentoOrder(): void
    {
        if ($this->getMagentoOrderId() !== null) {
            throw new \M2E\Temu\Model\Exception('Magento Order is already created.');
        }

        $reserve = $this->getReserve();

        if ($reserve->isPlaced()) {
            $reserve->setFlag('order_reservation', true);
            $reserve->release();
        }
    }

    // ----------------------------------------

    public function getBuyerName()
    {
        return $this->getData(OrderResource::COLUMN_BUYER_NAME);
    }

    public function setBuyerName(string $buyerName): self
    {
        $this->setData(OrderResource::COLUMN_BUYER_NAME, $buyerName);

        return $this;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getShippingDetails(): array
    {
        $value =  $this->getData(OrderResource::COLUMN_SHIPPING_DETAILS);
        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }

    public function setShippingDetails(array $details): self
    {
        $value = json_encode($details, JSON_THROW_ON_ERROR);
        $this->setData(OrderResource::COLUMN_SHIPPING_DETAILS, $value);

        return $this;
    }

    public function getReserve(): \M2E\Temu\Model\Order\Reserve
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->reserve)) {
            $this->reserve = $this->orderReserveFactory->create($this);
        }

        return $this->reserve;
    }

    // ----------------------------------------

    public function getProxy(): Order\ProxyObject
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->proxy)) {
            $this->proxy = $this->proxyObjectFactory->create($this);
        }

        return $this->proxy;
    }

    // ----------------------------------------

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addWarningLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ): bool {
        return $this->addLog(
            $description,
            Log::TYPE_WARNING,
            $params,
            $links,
            $isUnique,
            $additionalData
        );
    }

    // ----------------------------------------

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addLog(
        $description,
        $type,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ): bool {
        $log = $this->getLogService();

        if (!empty($params)) {
            $description = \M2E\Temu\Helper\Module\Log::encodeDescription($description, $params, $links);
        }

        return $log->addMessage(
            $this,
            $description,
            $type,
            $additionalData,
            $isUnique
        );
    }

    // ----------------------------------------

    public function getLogService(): \M2E\Temu\Model\Order\Log\Service
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->logService)) {
            $this->logService = $this->orderLogServiceFactory->create();
        }

        return $this->logService;
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function afterCreateMagentoOrder()
    {
        // add history comments
        // ---------------------------------------
        $magentoOrderUpdater = $this->magentoOrderUpdater;
        $magentoOrderUpdater->setMagentoOrder($this->getMagentoOrder());
        $magentoOrderUpdater->updateComments($this->getProxy()->getComments());
        $magentoOrderUpdater->finishUpdate();
        // ---------------------------------------

        $this->orderEventDispatcher->dispatchEventsMagentoOrderCreated($this);

        $this->addSuccessLog('Magento Order #%order_id% was created.', [
            '!order_id' => $this->getMagentoOrder()->getRealOrderId(),
        ]);

        if ($this->getAccount()->getOrdersSettings()->isCustomerNewNotifyWhenOrderCreated()) {
            $this->orderSender->send($this->getMagentoOrder());
        }
    }

    public function getMagentoOrder(): ?\Magento\Sales\Model\Order
    {
        if ($this->getMagentoOrderId() === null) {
            return null;
        }

        if ($this->magentoOrder === null) {
            $this->magentoOrder = $this->orderFactory->create()->load($this->getMagentoOrderId());
        }

        return $this->magentoOrder->getId() !== null ? $this->magentoOrder : null;
    }

    public function setMagentoOrder(\Magento\Sales\Model\Order $order): self
    {
        $this->magentoOrder = $order;

        return $this;
    }

    //########################################

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addSuccessLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ): bool {
        return $this->addLog(
            $description,
            Log::TYPE_SUCCESS,
            $params,
            $links,
            $isUnique,
            $additionalData
        );
    }

    //########################################

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addInfoLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ): bool {
        return $this->addLog(
            $description,
            Log::TYPE_INFO,
            $params,
            $links,
            $isUnique,
            $additionalData
        );
    }

    //########################################

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addErrorLog(
        $description,
        array $params = [],
        array $links = [],
        $isUnique = false,
        $additionalData = []
    ): bool {
        return $this->addLog(
            $description,
            Log::TYPE_ERROR,
            $params,
            $links,
            $isUnique,
            $additionalData
        );
    }

    public function isReservable(): bool
    {
        if ($this->getMagentoOrderId() !== null) {
            return false;
        }

        if ($this->getReserve()->isPlaced()) {
            return false;
        }

        if ($this->isCanceled()) {
            return false;
        }

        foreach ($this->getItems() as $item) {
            if (!$item->isReservable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function addCreatedMagentoShipment(\Magento\Sales\Model\Order\Shipment $magentoShipment): self
    {
        $additionalData = $this->getAdditionalData();
        $additionalData['created_shipments_ids'][] = $magentoShipment->getId();

        $this->setSettings('additional_data', $additionalData);

        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->getData(OrderResource::COLUMN_BUYER_EMAIL);
    }

    public function setBuyerEmail(string $email): self
    {
        $this->setData(OrderResource::COLUMN_BUYER_EMAIL, $email);

        return $this;
    }

    public function setBuyerPhone(?string $buyerPhone): self
    {
        $this->setData(OrderResource::COLUMN_BUYER_PHONE, $buyerPhone);

        return $this;
    }

    public function getBuyerPhone(): ?string
    {
        return $this->getData(OrderResource::COLUMN_BUYER_PHONE);
    }

    public function getCurrency()
    {
        return $this->getData(OrderResource::COLUMN_CURRENCY);
    }

    public function getPriceTotal(): float
    {
        return $this->getData(OrderResource::COLUMN_PRICE_TOTAL);
    }

    public function setPriceTotal(float $total): self
    {
        $this->setData(OrderResource::COLUMN_PRICE_TOTAL, $total);

        return $this;
    }

    /**
     * @return int|float
     */
    public function getTaxRate()
    {
        return $this->priceTaxRateFactory->createProductPriceTaxRateByOrder($this)->getValue();
    }

    public function getTaxDetails(): array
    {
        $value = $this->getData(OrderResource::COLUMN_TAX_DETAILS);
        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }

    public function setTaxDetails(array $details): self
    {
        $value = json_encode($details, JSON_THROW_ON_ERROR);
        $this->setData(OrderResource::COLUMN_TAX_DETAILS, $value);

        return $this;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getTaxAmount(): float
    {
        $taxDetails = $this->getTaxDetails();
        if (empty($taxDetails)) {
            return 0.0;
        }

        return (float)($taxDetails['amount'] ?? 0.0);
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function isShippingPriceHasTax(): bool
    {
        if (!$this->hasShippingTax()) {
            return false;
        }

        if ($this->isVatTax()) {
            return true;
        }

        $taxDetails = $this->getTaxDetails();

        return isset($taxDetails['includes_shipping']) && $taxDetails['includes_shipping'];
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function hasShippingTax(): bool
    {
        return $this->getShippingTax() > 0;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getShippingTax()
    {
        $taxDetails = $this->getTaxDetails();

        return $taxDetails['tax_delivery'] ?? 0.0;
    }

    public function getShippingService(): string
    {
        $trackingDetails = $this->getShippingTrackingDetails();

        return $trackingDetails[0]['supplier_name'] ?? '';
    }

    public function getShippingAddress(): \M2E\Temu\Model\Order\ShippingAddress
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->shippingAddress)) {
            $shippingDetails = $this->getShippingDetails();
            $address = $shippingDetails['address'] ?? [];

            $this->shippingAddress = $this->shippingAddressFactory
                ->create($this)
                ->setData($address);
        }

        return $this->shippingAddress;
    }

    public function getChannelUpdateDate(): \DateTime
    {
        $date = $this->getData(OrderResource::COLUMN_CHANNEL_UPDATE_DATE);

        return \M2E\Core\Helper\Date::createDateGmt($date);
    }

    public function setChannelUpdateDate(\DateTimeInterface $date): self
    {
        $this->setData(OrderResource::COLUMN_CHANNEL_UPDATE_DATE, $date->format('Y-m-d H:i:s'));

        return $this;
    }

    public function getPurchaseDate(): \DateTimeImmutable
    {
        $date = $this->getData(OrderResource::COLUMN_PURCHASE_DATE);

        return \M2E\Core\Helper\Date::createImmutableDateGmt($date);
    }

    public function getTotalPrice(): ?float
    {
        return (float)$this->getData(OrderResource::COLUMN_PRICE_TOTAL);
    }

    public function getGrandTotal(): float
    {
        return $this->getPriceTotal() + $this->getShippingPrice() + $this->getTaxAmount();
    }

    public function getShippingPrice(): float
    {
        $shippingPrice = $this->getPriceDelivery();

        return (float)$shippingPrice;
    }

    public function getShippingTrackingDetails(): array
    {
        $trackingDetails = [];
        foreach ($this->getItems() as $orderItem) {
            $itemTrackingDetails = $orderItem->getTrackingDetails();
            if (empty($itemTrackingDetails)) {
                continue;
            }

            $trackNumber = $itemTrackingDetails['tracking_number'] ?? null;
            if (empty($trackNumber)) {
                continue;
            }

            if (isset($trackingDetails[$trackNumber])) {
                $trackingDetails[$trackNumber]['order_items'][] = $orderItem;
                continue;
            }

            $trackingDetails[$trackNumber] = [
                'supplier_name' => $itemTrackingDetails['supplier_name'],
                'tracking_number' => $itemTrackingDetails['tracking_number'],
                'order_items' => [$orderItem],
            ];
        }

        return array_values($trackingDetails);
    }

    public function canUpdateShippingStatus(): bool
    {
        if (
            $this->isStatusPending()
            || $this->isStatusShipping()
            || $this->isStatusCanceled()
        ) {
            return false;
        }

        return true;
    }

    public function setPriceDelivery(float $priceDelivery): self
    {
        $this->setData(OrderResource::COLUMN_PRICE_DELIVERY, $priceDelivery);

        return $this;
    }

    public function getPriceDelivery(): ?float
    {
        $value = $this->getData(OrderResource::COLUMN_PRICE_DELIVERY);
        if ($value === null) {
            return null;
        }

        return (float)$value;
    }

    public function setPriceDiscount(float $priceDiscount): self
    {
        $this->setData(OrderResource::COLUMN_PRICE_DISCOUNT, $priceDiscount);

        return $this;
    }

    public function getPriceDiscount(): ?float
    {
        return $this->getData(OrderResource::COLUMN_PRICE_DISCOUNT);
    }

    public function hasPriceDiscount(): bool
    {
        return $this->getPriceDiscount() > 0;
    }

    public function setShipByDate(?\DateTimeInterface $shippedDate): self
    {
        $this->setData(
            OrderResource::COLUMN_SHIP_BY_DATE,
            $shippedDate === null
                ? null
                : $shippedDate->format('Y-m-d H:i:s')
        );

        return $this;
    }

    public function getShipByDate(): ?\DateTimeImmutable
    {
        $date = $this->getData(OrderResource::COLUMN_SHIP_BY_DATE);
        if ($date === null) {
            return null;
        }

        return \M2E\Core\Helper\Date::createImmutableDateGmt($date);
    }

    public function setShippingTime(?\DateTimeInterface $shippingTime): self
    {
        $this->setData(
            OrderResource::COLUMN_SHIPPING_TIME,
            $shippingTime === null
                ? null
                : $shippingTime->format('Y-m-d H:i:s')
        );

        return $this;
    }

    public function getShippingTime(): ?\DateTimeImmutable
    {
        $date = $this->getData(OrderResource::COLUMN_SHIPPING_TIME);
        if ($date === null) {
            return null;
        }

        return \M2E\Core\Helper\Date::createImmutableDateGmt($date);
    }

    public function setDeliverByDate(?\DateTimeInterface $deliverByDate): self
    {
        $this->setData(
            OrderResource::COLUMN_DELIVER_BY_DATE,
            $deliverByDate === null
                ? null
                : $deliverByDate->format('Y-m-d H:i:s')
        );

        return $this;
    }

    public function getDeliverByDate(): ?\DateTimeImmutable
    {
        $date = $this->getData(OrderResource::COLUMN_DELIVER_BY_DATE);

        if ($date === null) {
            return null;
        }

        return \M2E\Core\Helper\Date::createImmutableDateGmt($date);
    }

    public function markStatusUpdateRequired(): self
    {
        $this->statusUpdateRequired = true;

        return $this;
    }

    public function getStatusUpdateRequired(): bool
    {
        return $this->statusUpdateRequired;
    }

    // ----------------------------------------

    public function getMagentoOrderCreationFailsCount(): int
    {
        return (int)$this->getData('magento_order_creation_fails_count');
    }

    public function markMagentoOrderCreationFailure(): void
    {
        $this->setData(OrderResource::COLUMN_MAGENTO_ORDER_CREATION_FAILURE, self::MAGENTO_ORDER_CREATION_FAILED_YES)
             ->setData(
                 OrderResource::COLUMN_MAGENTO_ORDER_CREATION_FAILS_COUNT,
                 $this->getMagentoOrderCreationFailsCount() + 1
             )
             ->setData(
                 OrderResource::COLUMN_MAGENTO_ORDER_CREATION_LATEST_ATTEMPT_DATE,
                 \M2E\Core\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
             );
    }

    public function resetMagentoCreationAttempts(): void
    {
        $this->setData(OrderResource::COLUMN_MAGENTO_ORDER_CREATION_FAILURE, self::MAGENTO_ORDER_CREATION_FAILED_NO)
             ->setData(OrderResource::COLUMN_MAGENTO_ORDER_CREATION_FAILS_COUNT, 0)
             ->setData(OrderResource::COLUMN_MAGENTO_ORDER_CREATION_LATEST_ATTEMPT_DATE, null);
    }
}
