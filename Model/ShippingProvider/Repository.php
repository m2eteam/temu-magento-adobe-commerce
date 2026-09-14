<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ShippingProvider;

use M2E\Temu\Model\ResourceModel\ShippingProvider as ShippingProviderResource;
use M2E\Temu\Model\ResourceModel\ShippingProvider\CollectionFactory as ShippingProviderCollectionFactory;

class Repository
{
    private ShippingProviderCollectionFactory $collectionFactory;
    private ShippingProviderResource $shippingProviderResource;

    public function __construct(
        ShippingProviderCollectionFactory $collectionFactory,
        ShippingProviderResource $ShippingProviderResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->shippingProviderResource = $ShippingProviderResource;
    }

    public function create(\M2E\Temu\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->save($shippingProvider);
    }

    public function save(\M2E\Temu\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->save($shippingProvider);
    }

    /**
     * @return \M2E\Temu\Model\ShippingProvider[]
     */
    public function getByAccount(
        \M2E\Temu\Model\Account $account
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_ACCOUNT_ID, ['eq' => $account->getId()]);

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\Temu\Model\ShippingProvider[]
     */
    public function findByShippingProviderIds(array $shippingProviderIds): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
            ['in' => $shippingProviderIds]
        );

        return array_values($collection->getItems());
    }

    public function findByShippingProviderId(int $shippingProviderId): ?\M2E\Temu\Model\ShippingProvider
    {
        $providers = $this->findByShippingProviderIds([$shippingProviderId]);
        if (empty($providers)) {
            return null;
        }

        return reset($providers);
    }

    public function findExistedShippingProvider(
        \M2E\Temu\Model\ShippingProvider $object
    ): ?\M2E\Temu\Model\ShippingProvider {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_ACCOUNT_ID, $object->getAccountId());
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
            $object->getShippingProviderId()
        );

        /** @var \M2E\Temu\Model\ShippingProvider $shippingProvider */
        $shippingProvider = $collection->getFirstItem();

        if ($shippingProvider->isObjectNew()) {
            return null;
        }

        return $shippingProvider;
    }

    public function findByAccountRegionAndTitle(
        int $accountId,
        int $regionId,
        string $title
    ): ?\M2E\Temu\Model\ShippingProvider {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_REGION_ID,
            ['eq' => $regionId]
        );
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_NAME,
            ['eq' => trim($title)]
        );

        $shippingProvider = $collection->getFirstItem();
        if ($shippingProvider->isObjectNew()) {
            return null;
        }

        return $shippingProvider;
    }

    public function delete(\M2E\Temu\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->delete($shippingProvider);
    }
}
