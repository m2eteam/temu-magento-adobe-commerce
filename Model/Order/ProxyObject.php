<?php

namespace M2E\Temu\Model\Order;

use M2E\Temu\Model\Magento\Payment as TemuPayment;

class ProxyObject
{
    public const CHECKOUT_GUEST = 'guest';
    public const CHECKOUT_REGISTER = 'register';

    protected \M2E\Temu\Model\Currency $currency;
    protected TemuPayment $payment;
    protected \M2E\Temu\Model\Order $order;
    protected \Magento\Customer\Model\CustomerFactory $customerFactory;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    /** @var \M2E\Temu\Model\Order\Item\ProxyObject[] */
    protected ?array $items = null;
    protected \Magento\Store\Api\Data\StoreInterface $store;
    protected array $addressData = [];

    private UserInfoFactory $userInfoFactory;
    protected \Magento\Tax\Model\Calculation $taxCalculation;
    private \M2E\Core\Model\Magento\CustomerFactory $magentoCustomerFactory;
    private \M2E\Temu\Model\Config\Manager $config;
    private \M2E\Temu\Model\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory;

    public function __construct(
        \M2E\Temu\Model\Order $order,
        \M2E\Temu\Model\Config\Manager $config,
        \M2E\Core\Model\Magento\CustomerFactory $magentoCustomerFactory,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \M2E\Temu\Model\Currency $currency,
        TemuPayment $payment,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \M2E\Temu\Model\Order\UserInfoFactory $userInfoFactory,
        \M2E\Temu\Model\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory
    ) {
        $this->order = $order;
        $this->config = $config;
        $this->currency = $currency;
        $this->payment = $payment;
        $this->userInfoFactory = $userInfoFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->taxCalculation = $taxCalculation;
        $this->magentoCustomerFactory = $magentoCustomerFactory;
        $this->priceTaxRateFactory = $priceTaxRateFactory;
    }

    public function createUserInfoFromRawName(string $rawName): UserInfo
    {
        return $this->userInfoFactory->create($rawName, $this->getStore());
    }

    /**
     * @return \M2E\Temu\Model\Order\Item\ProxyObject[]
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getItems(): array
    {
        if ($this->items === null) {
            $items = [];

            foreach ($this->order->getItems() as $item) {
                if ($item->isStatusCanceled()) {
                    continue;
                }

                $proxyItem = $item->getProxy();
                if ($proxyItem->getQty() <= 0) {
                    continue;
                }

                $items[] = $proxyItem;
            }

            $this->items = $this->mergeItems($items);
        }

        return $this->items;
    }

    /**
     * Order may have multiple items ordered, but some of them may be mapped to single product in magento.
     * We have to merge them to avoid qty and price calculation issues.
     *
     * @param \M2E\Temu\Model\Order\Item\ProxyObject[] $items
     *
     * @return \M2E\Temu\Model\Order\Item\ProxyObject[]
     */
    protected function mergeItems(array $items)
    {
        $unsetItems = [];

        foreach ($items as $key => &$item) {
            if (in_array($key, $unsetItems)) {
                continue;
            }

            foreach ($items as $nestedKey => $nestedItem) {
                if ($key == $nestedKey) {
                    continue;
                }

                if (!$item->equals($nestedItem)) {
                    continue;
                }

                $item->merge($nestedItem);

                $unsetItems[] = $nestedKey;
            }
        }

        foreach ($unsetItems as $key) {
            unset($items[$key]);
        }

        return $items;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return $this
     */
    public function setStore(\Magento\Store\Api\Data\StoreInterface $store): self
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return \Magento\Store\Model\Store
     * @throws \M2E\Temu\Model\Exception
     */
    public function getStore()
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck  */
        if (!isset($this->store)) {
            throw new \M2E\Temu\Model\Exception('Store is not set.');
        }

        /** @psalm-suppress NoValue  */
        return $this->store;
    }

    public function getCheckoutMethod(): string
    {
        if (
            $this->order->getAccount()->getOrdersSettings()->isCustomerPredefined()
            || $this->order->getAccount()->getOrdersSettings()->isCustomerNew()
        ) {
            return self::CHECKOUT_REGISTER;
        }

        return self::CHECKOUT_GUEST;
    }

    /**
     * @return bool
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function isCheckoutMethodGuest()
    {
        return $this->getCheckoutMethod() == self::CHECKOUT_GUEST;
    }

    public function isOrderNumberPrefixSourceMagento(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isMagentoOrdersNumberSourceMagento();
    }

    public function isOrderNumberPrefixSourceChannel(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isMagentoOrdersNumberSourceChannel();
    }

    public function getOrderNumberPrefix(): string
    {
        return $this->order->getAccount()->getOrdersSettings()->getMagentoOrdersNumberRegularPrefix();
    }

    public function getChannelOrderNumber()
    {
        return $this->order->getChannelOrderId();
    }

    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isCustomerNewNotifyWhenOrderCreated();
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws \M2E\Temu\Model\Exception
     * @throws \M2E\Temu\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer(): ?\Magento\Customer\Api\Data\CustomerInterface
    {
        $accountModel = $this->order->getAccount();

        if ($accountModel->getOrdersSettings()->isCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $accountModel->getOrdersSettings()->getCustomerPredefinedId()
            );

            if ($customerDataObject->getId() === null) {
                throw new \M2E\Temu\Model\Exception(
                    "Customer with ID specified in Temu Account
                    Settings does not exist."
                );
            }

            return $customerDataObject;
        }

        $customerBuilder = $this->magentoCustomerFactory->create();

        if ($accountModel->getOrdersSettings()->isCustomerNew()) {
            $customerInfo = $this->getAddressData();

            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($accountModel->getOrdersSettings()->getCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if ($customerObject->getId() !== null) {
                $customerBuilder->setData($customerInfo);
                $customerBuilder->updateAddress($customerObject);

                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $accountModel->getOrdersSettings()->getCustomerNewWebsiteId();
            $customerInfo['group_id'] = $accountModel->getOrdersSettings()->getCustomerNewGroupId();

            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();
            $customerBuilder->getCustomer()->save();

            return $customerBuilder->getCustomer()->getDataModel();
        }

        return null;
    }

    public function getCustomerFirstName()
    {
        $addressData = $this->getAddressData();

        return $addressData['firstname'];
    }

    public function getCustomerLastName()
    {
        $addressData = $this->getAddressData();

        return $addressData['lastname'];
    }

    public function getBuyerEmail()
    {
        $addressData = $this->getAddressData();

        return $addressData['email'];
    }

    /**
     * @return array
     */
    public function getAddressData(): array
    {
        if (empty($this->addressData)) {
            $rawAddressData = $this->order->getShippingAddress()->getRawData();

            $recipientUserInfo = $this->createUserInfoFromRawName($rawAddressData['recipient_name']);
            $this->addressData['prefix'] = $recipientUserInfo->getPrefix();
            $this->addressData['firstname'] = $recipientUserInfo->getFirstName();
            $this->addressData['middlename'] = $recipientUserInfo->getMiddleName();
            $this->addressData['lastname'] = $recipientUserInfo->getLastName();
            $this->addressData['suffix'] = $recipientUserInfo->getSuffix();

            $customerUserInfo = $this->createUserInfoFromRawName($rawAddressData['buyer_name']);
            $this->addressData['customer_prefix'] = $customerUserInfo->getPrefix();
            $this->addressData['customer_firstname'] = $customerUserInfo->getFirstName();
            $this->addressData['customer_middlename'] = $customerUserInfo->getMiddleName();
            $this->addressData['customer_lastname'] = $customerUserInfo->getLastName();
            $this->addressData['customer_suffix'] = $customerUserInfo->getSuffix();

            $this->addressData['email'] = $rawAddressData['email'];
            $this->addressData['country_id'] = $rawAddressData['country_id'];
            $this->addressData['region'] = $rawAddressData['region'];
            $this->addressData['region_id'] = $this->order->getShippingAddress()->getRegionId();
            $this->addressData['city'] = $rawAddressData['city'];
            $this->addressData['postcode'] = $rawAddressData['postcode'];
            $this->addressData['telephone'] = $rawAddressData['telephone'];
            $this->addressData['street'] = !empty($rawAddressData['street'])
                ? array_filter($rawAddressData['street'])
                : '';
            $this->addressData['company'] = !empty($rawAddressData['company']) ? $rawAddressData['company'] : '';
            $this->addressData['save_in_address_book'] = 0;
        }

        return $this->addressData;
    }

    public function getBillingAddressData(): array
    {
        return $this->getAddressData();
    }

    public function shouldIgnoreBillingAddressValidation(): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    public function convertPrice($price)
    {
        return $this->currency->convertPrice($price, $this->getCurrency(), $this->getStore());
    }

    public function convertPriceToBase($price)
    {
        return $this->currency->convertPriceToBaseCurrency($price, $this->getCurrency(), $this->getStore());
    }

    public function getPaymentData(): array
    {
        return [
            \Magento\Quote\Api\Data\PaymentInterface::KEY_METHOD => $this->payment->getCode(),
            \Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA => [
                TemuPayment::ADDITIONAL_DATA_KEY_PAYMENT_METHOD => '',
                TemuPayment::ADDITIONAL_DATA_KEY_CHANNEL_ORDER_ID => $this->order->getChannelOrderId(),
            ]
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getShippingData(): array
    {
        $additionalData = '';

        $shippingDateTo = $this->order->getShipByDate();
        $isImportShipByDate = $this
            ->order
            ->getAccount()
            ->getOrdersSettings()
            ->isImportShipByDate();

        if (!empty($shippingDateTo) && $isImportShipByDate) {
            $additionalData .= sprintf('Ship By Date: %s | ', $shippingDateTo->format('M d, Y, H:i:s'));
        }

        if (!empty($additionalData)) {
            $additionalData = ' | ' . $additionalData;
        }

        $shippingMethod = $this->order->getShippingService();

        return [
            'carrier_title' => (string)__(
                '%channel_title Delivery Option',
                [
                    'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                ],
            ),
            'shipping_method' => $shippingMethod . $additionalData,
            'shipping_price' => $this->getBaseShippingPrice(),
        ];
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        $price = $this->order->getShippingPrice();

        if ($this->isTaxModeNone() && !$this->isShippingPriceIncludeTax()) {
            $taxAmount = $this->taxCalculation->calcTaxAmount(
                $price,
                $this->getShippingPriceTaxRate(),
                false,
                false
            );

            $price += $taxAmount;
        }

        return $price;
    }

    protected function getBaseShippingPrice()
    {
        return $this->convertPriceToBase($this->getShippingPrice());
    }

    public function hasTax(): bool
    {
        return $this->order->getTaxRate() > 0;
    }

    /**
     * @return int|float
     */
    public function getTaxRate()
    {
        return $this->order->getTaxRate();
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        return $this->order->getTaxRate();
    }

    public function getProductPriceTaxRateObject(): \M2E\Temu\Model\Order\Tax\PriceTaxRateInterface
    {
        return $this->priceTaxRateFactory->createProductPriceTaxRateByOrder($this->order);
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        if (!$this->order->isShippingPriceHasTax()) {
            return 0;
        }

        return $this->getProductPriceTaxRate();
    }

    /**
     * @return \M2E\Temu\Model\Order\Tax\PriceTaxRateInterface|null
     */
    public function getShippingPriceTaxRateObject(): ?\M2E\Temu\Model\Order\Tax\PriceTaxRateInterface
    {
        return null;
    }

    // ---------------------------------------

    /**
     * @return bool|null
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function isProductPriceIncludeTax(): ?bool
    {
        return false;
    }

    /**
     * @return bool|null
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function isShippingPriceIncludeTax(): ?bool
    {
        return $this->isPriceIncludeTax('shipping');
    }

    /**
     * @param $priceType
     *
     * @return bool|null
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    protected function isPriceIncludeTax(string $priceType): ?bool
    {
        $configValue = $this->config->get("/order/tax/{$priceType}_price/", 'is_include_tax');
        if ($configValue !== null) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return true;
        }

        return null;
    }

    public function isTaxModeNone(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeNone();
    }

    public function isTaxModeChannel(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeChannel();
    }

    public function isTaxModeMagento(): bool
    {
        return $this->order->getAccount()->getOrdersSettings()->isTaxModeMagento();
    }

    public function isTaxModeMixed(): bool
    {
        return !$this->isTaxModeNone() &&
            !$this->isTaxModeChannel() &&
            !$this->isTaxModeMagento();
    }

    public function getComments(): array
    {
        return array_merge($this->getGeneralComments(), $this->getChannelComments());
    }

    /**
     * @return array
     */
    public function getChannelComments()
    {
        return [];
    }

    /**
     * @return array
     * @throws \M2E\Temu\Model\Exception
     */
    public function getGeneralComments()
    {
        $store = $this->getStore();

        $currencyConvertRate = $this->currency->getConvertRateFromBase($this->getCurrency(), $store, 4);

        if ($this->currency->isBase($this->getCurrency(), $store)) {
            return [];
        }

        $comments = [];

        if (!$this->currency->isAllowed($this->getCurrency(), $store)) {
            $comments[] = (string)__(
                '<b>Attention!</b> The Order Prices are incorrect. Conversion was not ' .
                'performed as "%order_currency" Currency is not enabled. Default ' .
                'Currency "%store_currency" was used instead. Please, ' .
                'enable Currency in System > Configuration > Currency Setup.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                ]
            );
        } elseif ($currencyConvertRate == 0) {
            $comments[] = __(
                '<b>Attention!</b> The Order Prices are incorrect. Conversion was not ' .
                'performed as there\'s no rate for "%order_currency". Default Currency ' .
                '"%store_currency" was used instead. Please, add Currency convert ' .
                'rate in System > Manage Currency > Rates.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                ]
            );
        } else {
            $comments[] = __(
                'Because the Order Currency is different from the Store Currency, the conversion ' .
                'from <b>"%order_currency" to "%store_currency"</b> was performed ' .
                'using <b>%currency_rate</b> as a rate.',
                [
                    'order_currency' => $this->getCurrency(),
                    'store_currency' => $store->getBaseCurrencyCode(),
                    'currency_rate' => $currencyConvertRate
                ]
            );
        }

        return $comments;
    }
}
