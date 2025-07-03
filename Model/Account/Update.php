<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Account;

use M2E\Temu\Model\Account\Issue\ValidTokens;

class Update
{
    private \M2E\Temu\Model\Channel\Connector\Account\Update\Processor $updateProcessor;
    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private \M2E\Temu\Helper\Data\Cache\Permanent $cache;
    private \M2E\Temu\Model\ShippingProvider\SynchronizeService $shippingProviderSynchronizeService;
    private \M2E\Temu\Model\Account\ShippingMappingUpdater $shippingMappingUpdater;
    private \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage;

    public function __construct(
        \M2E\Temu\Model\Channel\Connector\Account\Update\Processor $updateProcessor,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\ShippingProvider\SynchronizeService $shippingProviderSynchronizeService,
        \M2E\Temu\Model\Account\ShippingMappingUpdater $shippingMappingUpdater,
        \M2E\Temu\Helper\Data\Cache\Permanent $cache,
        \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage
    ) {
        $this->updateProcessor = $updateProcessor;
        $this->accountRepository = $accountRepository;
        $this->shippingProviderSynchronizeService = $shippingProviderSynchronizeService;
        $this->shippingMappingUpdater = $shippingMappingUpdater;
        $this->cache = $cache;
        $this->authCodeSessionStorage = $authCodeSessionStorage;
    }

    public function updateSettings(
        \M2E\Temu\Model\Account $account,
        string $title,
        \M2E\Temu\Model\Account\Settings\UnmanagedListings $unmanagedListingsSettings,
        \M2E\Temu\Model\Account\Settings\Order $orderSettings,
        \M2E\Temu\Model\Account\Settings\InvoicesAndShipment $invoicesAndShipmentSettings,
        array $shippingProviderMapping
    ): void {
        $account->setTitle($title)
            ->setUnmanagedListingSettings($unmanagedListingsSettings)
            ->setOrdersSettings($orderSettings)
            ->setInvoiceAndShipmentSettings($invoicesAndShipmentSettings);

        $shippingMapping = $this->shippingMappingUpdater->prepareShippingProviderData($shippingProviderMapping);
        $account->setShippingProviderMapping($shippingMapping);

        $this->accountRepository->save($account);
    }

    public function updateCredentials(\M2E\Temu\Model\Account $account, string $authCode): void
    {
        if ($this->authCodeSessionStorage->getAccount($authCode) !== null) {
            return;
        }

        $this->updateProcessor->process(
            $account,
            $authCode
        );

        $this->authCodeSessionStorage->setAccount($authCode, $account->getId());

        $this->cache->removeValue(ValidTokens::ACCOUNT_TOKENS_CACHE_KEY);
    }

    public function refresh(\M2E\Temu\Model\Account $account): void
    {
        $this->shippingProviderSynchronizeService->synchronizeShippingProviders($account);
    }
}
