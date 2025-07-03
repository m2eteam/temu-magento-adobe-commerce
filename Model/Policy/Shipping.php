<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy;

use M2E\Temu\Model\ResourceModel\Policy\Shipping as ShippingResource;

class Shipping extends \M2E\Temu\Model\ActiveRecord\AbstractModel implements PolicyInterface
{
    private \M2E\Temu\Model\Account\Repository $accountRepository;
    private \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;
    private \M2E\Temu\Model\Account $account;

    public function __construct(
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $context,
            $registry
        );

        $this->accountRepository = $accountRepository;
        $this->listingCollectionFactory = $listingCollectionFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\M2E\Temu\Model\ResourceModel\Policy\Shipping::class);
    }

    public function create(
        int $accountId,
        string $title,
        string $shippingTemplateId,
        int $preparationTime
    ): self {
        $this->setTitle($title);
        $this->setData(ShippingResource::COLUMN_ACCOUNT_ID, $accountId)
             ->setShippingTemplateId($shippingTemplateId)
             ->setPreparationTime($preparationTime);

        return $this;
    }

    public function setTitle(string $title): void
    {
        $this->setData(ShippingResource::COLUMN_TITLE, $title);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(ShippingResource::COLUMN_TITLE);
    }

    public function getNick(): string
    {
        return \M2E\Temu\Model\Policy\Manager::TEMPLATE_SHIPPING;
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(ShippingResource::COLUMN_ACCOUNT_ID);
    }

    public function getShippingTemplateId(): string
    {
        return $this->getData(ShippingResource::COLUMN_SHIPPING_TEMPLATE_ID);
    }

    public function setShippingTemplateId(string $shippingTemplateId): self
    {
        $this->setData(ShippingResource::COLUMN_SHIPPING_TEMPLATE_ID, $shippingTemplateId);

        return $this;
    }

    public function getPreparationTime(): int
    {
        return (int)$this->getData(ShippingResource::COLUMN_PREPARATION_TIME);
    }

    public function setPreparationTime(int $preparationTime): self
    {
        $this->setData(ShippingResource::COLUMN_PREPARATION_TIME, $preparationTime);

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

    public function isLocked(): bool
    {
        return (bool)$this
            ->listingCollectionFactory
            ->create()
            ->addFieldToFilter(
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_TEMPLATE_SHIPPING_ID,
                $this->getId()
            )
            ->getSize();
    }
}
