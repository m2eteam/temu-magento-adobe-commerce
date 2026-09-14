<?php

namespace M2E\Temu\Model;

use M2E\Temu\Helper\Component\Temu as ComponentHelper;
use M2E\Temu\Model\ResourceModel\Account as AccountResource;

class Account extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public const LOCK_NICK = 'account';

    private const REGION_US = 'US';
    private const REGION_EU = 'EU';
    private const REGION_GLOBAL = 'GLOBAL';

    public const SITE_ID_CANADA = 101;
    public const SITE_ID_MEXICO = 110;

    private Account\Settings\UnmanagedListings $unmanagedListingSettings;
    private Account\Settings\Order $ordersSettings;
    private Account\Settings\InvoicesAndShipment $invoiceAndShipmentSettings;
    private \M2E\Temu\Model\Account\ShippingMappingFactory $shippingMappingFactory;

    public function __construct(
        \M2E\Temu\Model\Account\ShippingMappingFactory $shippingMappingFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->shippingMappingFactory = $shippingMappingFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\M2E\Temu\Model\ResourceModel\Account::class);
    }

    public function create(
        string $title,
        string $identifier,
        string $serverHash,
        int $siteId,
        string $siteTitle,
        string $region,
        \M2E\Temu\Model\Account\Settings\UnmanagedListings $unmanagedListingsSettings,
        \M2E\Temu\Model\Account\Settings\Order $orderSettings,
        \M2E\Temu\Model\Account\Settings\InvoicesAndShipment $invoicesAndShipmentSettings
    ): self {
        $this
            ->setTitle($title)
            ->setData(AccountResource::COLUMN_IDENTIFIER, $identifier)
            ->setData(AccountResource::COLUMN_SERVER_HASH, $serverHash)
            ->setSiteId($siteId)
            ->setSiteTitle($siteTitle)
            ->setRegion($region)
            ->setUnmanagedListingSettings($unmanagedListingsSettings)
            ->setOrdersSettings($orderSettings)
            ->setInvoiceAndShipmentSettings($invoicesAndShipmentSettings);

        return $this;
    }

    // ----------------------------------------

    public function setTitle(string $title): self
    {
        $this->setData(AccountResource::COLUMN_TITLE, $title);

        return $this;
    }

    public function getTitle()
    {
        return $this->getData(AccountResource::COLUMN_TITLE);
    }

    public function getServerHash()
    {
        return $this->getData(AccountResource::COLUMN_SERVER_HASH);
    }

    public function getIdentifier(): string
    {
        return (string)$this->getData(AccountResource::COLUMN_IDENTIFIER);
    }

    public function setSiteId(int $siteId): self
    {
        $this->setData(AccountResource::COLUMN_SITE_ID, $siteId);

        return $this;
    }

    public function getSiteId(): int
    {
        return $this->getData(AccountResource::COLUMN_SITE_ID);
    }

    public function setSiteTitle(string $siteTitle): self
    {
        $this->setData(AccountResource::COLUMN_SITE_TITLE, $siteTitle);

        return $this;
    }

    public function getSiteTitle(): string
    {
        return $this->getData(AccountResource::COLUMN_SITE_TITLE);
    }

    // ----------------------------------------

    public function setRegion(string $region): self
    {
        $this->setData(AccountResource::COLUMN_REGION, $region);

        return $this;
    }

    public function getRegion(): string
    {
        return $this->getData(AccountResource::COLUMN_REGION);
    }

    public function isRegionUs(): bool
    {
        return strtoupper($this->getRegion()) === self::REGION_US;
    }

    public function isRegionEU(): bool
    {
        return strtoupper($this->getRegion()) === self::REGION_EU;
    }

    public function isRegionGlobal(): bool
    {
        return strtoupper($this->getRegion()) === self::REGION_GLOBAL;
    }

    // ----------------------------------------

    public function getCurrencyCode(): string
    {
        return ComponentHelper::getCurrencyCodeBySiteId(
            $this->getSiteId()
        );
    }

    public function setUnmanagedListingSettings(
        \M2E\Temu\Model\Account\Settings\UnmanagedListings $settings
    ): self {
        $this->unmanagedListingSettings = $settings;
        $this
            ->setData(AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION, (int)$settings->isSyncEnabled())
            ->setData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE, (int)$settings->isMappingEnabled())
            ->setData(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS,
                json_encode(
                    [
                        'sku' => $settings->getMappingBySkuSettings(),
                        'title' => $settings->getMappingByTitleSettings(),
                    ],
                ),
            )
            ->setData(
                AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORE_ID,
                $settings->getRelatedStoreId(),
            );

        return $this;
    }

    public function getUnmanagedListingSettings(): \M2E\Temu\Model\Account\Settings\UnmanagedListings
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->unmanagedListingSettings)) {
            return $this->unmanagedListingSettings;
        }

        $mappingSettings = $this->getData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS);
        $mappingSettings = json_decode($mappingSettings, true);

        $settings = new \M2E\Temu\Model\Account\Settings\UnmanagedListings();

        return $this->unmanagedListingSettings = $settings
            ->createWithSync((bool)$this->getData(AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION))
            ->createWithMapping((bool)$this->getData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE))
            ->createWithMappingSettings(
                $mappingSettings['sku'] ?? [],
                $mappingSettings['title'] ?? []
            )
            ->createWithRelatedStoreId(
                (int)$this->getData(AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORE_ID),
            );
    }

    public function setOrdersSettings(\M2E\Temu\Model\Account\Settings\Order $settings): self
    {
        $this->ordersSettings = $settings;

        $data = $settings->toArray();

        $this->setData(AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS, json_encode($data));

        return $this;
    }

    public function getOrdersSettings(): \M2E\Temu\Model\Account\Settings\Order
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->ordersSettings)) {
            return $this->ordersSettings;
        }

        $data = json_decode($this->getData(AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS), true);

        $settings = new \M2E\Temu\Model\Account\Settings\Order();

        return $this->ordersSettings = $settings->createWith($data);
    }

    public function setInvoiceAndShipmentSettings(
        \M2E\Temu\Model\Account\Settings\InvoicesAndShipment $settings
    ): self {
        $this->invoiceAndShipmentSettings = $settings;

        $this
            ->setData(AccountResource::COLUMN_CREATE_MAGENTO_INVOICE, (int)$settings->isCreateMagentoInvoice())
            ->setData(AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT, (int)$settings->isCreateMagentoShipment())
            ->setData(
                AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
                (int)$settings->isMapShippingProviderByCustomCarrierTitle()
            );

        return $this;
    }

    public function getInvoiceAndShipmentSettings(): \M2E\Temu\Model\Account\Settings\InvoicesAndShipment
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->invoiceAndShipmentSettings)) {
            return $this->invoiceAndShipmentSettings;
        }

        $settings = new \M2E\Temu\Model\Account\Settings\InvoicesAndShipment();

        return $this->invoiceAndShipmentSettings = $settings
            ->createWithMagentoInvoice((bool)$this->getData(AccountResource::COLUMN_CREATE_MAGENTO_INVOICE))
            ->createWithMagentoShipment((bool)$this->getData(AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT))
            ->createWithMapShippingProviderByCustomCarrierTitle(
                (bool)$this->getData(AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE)
            );
    }

    public function setShippingProviderMapping(
        \M2E\Temu\Model\Account\ShippingMapping $shippingMapping
    ): self {
        $json = json_encode($shippingMapping->toArray(), JSON_THROW_ON_ERROR);
        $this->setData(AccountResource::COLUMN_SHIPPING_PROVIDER_MAPPING, $json);

        return $this;
    }

    public function getShippingProviderMapping(): \M2E\Temu\Model\Account\ShippingMapping
    {
        $mapping = $this->getData(AccountResource::COLUMN_SHIPPING_PROVIDER_MAPPING);
        if (empty($mapping)) {
            return $this->shippingMappingFactory->create([]);
        }

        return $this->shippingMappingFactory->create(json_decode($mapping, true));
    }

    public function getCreateData(): \DateTimeImmutable
    {
        $value = $this->getData(AccountResource::COLUMN_CREATE_DATE);

        return \M2E\Core\Helper\Date::createImmutableDateGmt($value);
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersStatusMappingDefault(): bool
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['order_status_mapping', 'mode'],
            \M2E\Temu\Model\Account\Settings\Order::ORDERS_STATUS_MAPPING_MODE_DEFAULT
        );

        return $setting == \M2E\Temu\Model\Account\Settings\Order::ORDERS_STATUS_MAPPING_MODE_DEFAULT;
    }

    public function setInventoryLastSyncDate(\DateTimeInterface $date): self
    {
        $this->setData(AccountResource::COLUMN_INVENTORY_LAST_SYNC_DATE, $date);

        return $this;
    }

    public function getInventoryLastSyncDate(): ?\DateTimeImmutable
    {
        $value = $this->getData(AccountResource::COLUMN_INVENTORY_LAST_SYNC_DATE);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createImmutableDateGmt($value);
    }

    public function setOrdersLastSyncDate(\DateTimeInterface $date): self
    {
        $this->setData(AccountResource::COLUMN_ORDER_LAST_SYNC, $date);

        return $this;
    }

    public function getOrdersLastSyncDate(): ?\DateTimeImmutable
    {
        $value = $this->getData(AccountResource::COLUMN_ORDER_LAST_SYNC);
        if (empty($value)) {
            return null;
        }

        return \M2E\Core\Helper\Date::createImmutableDateGmt($value);
    }

    public function resetInventoryLastSyncDate(): self
    {
        $this->setData(AccountResource::COLUMN_INVENTORY_LAST_SYNC_DATE);

        return $this;
    }
}
