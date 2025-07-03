<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Account;

class Create
{
    private \M2E\Temu\Model\Channel\Connector\Account\Add\Processor $addProcessor;
    private Repository $accountRepository;
    private \M2E\Temu\Model\AccountFactory $accountFactory;
    private \M2E\Core\Helper\Magento\Store $storeHelper;
    private \M2E\Temu\Model\ShippingProvider\SynchronizeService $shippingProviderSynchronizeService;
    private \M2E\Temu\Helper\Module\Exception $helperException;
    private \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage;

    public function __construct(
        \M2E\Temu\Model\AccountFactory $accountFactory,
        \M2E\Temu\Model\Channel\Connector\Account\Add\Processor $addProcessor,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\ShippingProvider\SynchronizeService $shippingProviderSynchronizeService,
        \M2E\Core\Helper\Magento\Store $storeHelper,
        \M2E\Temu\Helper\Module\Exception $helperException,
        \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage
    ) {
        $this->addProcessor = $addProcessor;
        $this->accountRepository = $accountRepository;
        $this->accountFactory = $accountFactory;
        $this->shippingProviderSynchronizeService = $shippingProviderSynchronizeService;
        $this->storeHelper = $storeHelper;
        $this->helperException = $helperException;
        $this->authCodeSessionStorage = $authCodeSessionStorage;
    }

    /**
     * @param string $title
     * @param string $token
     * @param string $region
     *
     * @return \M2E\Temu\Model\Account
     * @throws \M2E\Core\Model\Exception
     * @throws \M2E\Core\Model\Exception\Connection
     * @throws \M2E\Temu\Model\Exception
     * @throws \M2E\Temu\Model\Exception\UnableAccountCreate
     */
    public function create(
        string $authCode,
        string $region,
        ?string $referer,
        ?string $callbackHost
    ): \M2E\Temu\Model\Account {
        $accountId = $this->authCodeSessionStorage->getAccount($authCode);
        if ($accountId !== null) {
            $account = $this->accountRepository->find($accountId);
            if ($account !== null) {
                return $account;
            }
        }

        $response = $this->createOnServer(
            $authCode,
            $region,
            $referer,
            $callbackHost
        );

        $channelAccount = $response->getAccount();
        $existAccount = $this->findExistAccountByIdentifier($channelAccount->identifier);
        if ($existAccount !== null) {
            throw new \M2E\Temu\Model\Exception(
                'An account with the same details has already been added. Please make sure you provide unique information.',
            );
        }

        $account = $this->accountFactory->create(
            $channelAccount->identifier,
            $channelAccount->identifier,
            $response->getHash(),
            $channelAccount->siteId,
            $channelAccount->siteTitle,
            $region,
            new \M2E\Temu\Model\Account\Settings\UnmanagedListings(),
            (new \M2E\Temu\Model\Account\Settings\Order())
                ->createWith(
                    ['listing_other' => ['store_id' => $this->storeHelper->getDefaultStoreId()]],
                ),
            new \M2E\Temu\Model\Account\Settings\InvoicesAndShipment(),
        );

        $this->accountRepository->create($account);
        $this->authCodeSessionStorage->setAccount($authCode, $account->getId());

        try {
            $this->synchronizeShippingProvider($account);
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);
        }

        return $account;
    }

    // ----------------------------------------

    /**
     * @param string $authCode
     * @param string $region
     *
     * @return \M2E\Temu\Model\Channel\Connector\Account\Add\Response
     * @throws \M2E\Core\Model\Exception
     * @throws \M2E\Core\Model\Exception\Connection
     * @throws \M2E\Temu\Model\Exception\UnableAccountCreate
     */
    private function createOnServer(
        string $authCode,
        string $region,
        ?string $referer,
        ?string $callbackHost
    ): \M2E\Temu\Model\Channel\Connector\Account\Add\Response {
        return $this->addProcessor->process(
            $authCode,
            $region,
            $referer,
            $callbackHost
        );
    }

    private function findExistAccountByIdentifier(string $identifier): ?\M2E\Temu\Model\Account
    {
        return $this->accountRepository->findByIdentifier($identifier);
    }

    private function synchronizeShippingProvider(\M2E\Temu\Model\Account $account): void
    {
        $this->shippingProviderSynchronizeService->synchronizeShippingProviders($account);
    }
}
