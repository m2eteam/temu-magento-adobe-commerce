<?php

namespace M2E\Temu\Model\Policy;

use M2E\Temu\Model\ResourceModel\Policy\Synchronization as SynchronizationResource;

class Synchronization extends \M2E\Temu\Model\ActiveRecord\AbstractModel implements PolicyInterface
{
    public const QTY_MODE_NONE = 0;
    public const QTY_MODE_YES = 1;

    public const LIST_ADVANCED_RULES_PREFIX = 'template_synchronization_list_advanced_rules';
    public const STOP_ADVANCED_RULES_PREFIX = 'template_synchronization_stop_advanced_rules';
    public const RELIST_ADVANCED_RULES_PREFIX = 'template_synchronization_relist_advanced_rules';

    private \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;

    public function __construct(
        \M2E\Temu\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
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
        $this->listingCollectionFactory = $listingCollectionFactory;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\M2E\Temu\Model\ResourceModel\Policy\Synchronization::class);
    }

    public function getNick(): string
    {
        return \M2E\Temu\Model\Policy\Manager::TEMPLATE_SYNCHRONIZATION;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return (bool)$this->listingCollectionFactory
            ->create()
            ->addFieldToFilter(
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
                $this->getId()
            )
            ->getSize();
    }

    //########################################

    public function setTitle(string $title): void
    {
        $this->setData(SynchronizationResource::COLUMN_TITLE, $title);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(SynchronizationResource::COLUMN_TITLE);
    }

    public function isListMode(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_MODE) != 0;
    }

    public function isListStatusEnabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_STATUS_ENABLED) != 0;
    }

    public function isListIsInStock(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_IS_IN_STOCK) != 0;
    }

    public function isListWhenQtyCalculatedHasValue(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_QTY_CALCULATED) != self::QTY_MODE_NONE;
    }

    public function isListAdvancedRulesEnabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_ADVANCED_RULES_MODE) != 0
            && !empty($this->getListAdvancedRulesFilters());
    }

    public function getListAdvancedRulesFilters()
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_ADVANCED_RULES_FILTERS);
    }

    // ---------------------------------------

    public function getReviseUpdateQtyMaxAppliedValueMode(): int
    {
        return (int)$this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_MODE);
    }

    public function isReviseUpdateQtyMaxAppliedValueModeOn(): bool
    {
        return $this->getReviseUpdateQtyMaxAppliedValueMode() == 1;
    }

    public function getReviseUpdateQtyMaxAppliedValue(): int
    {
        return (int)$this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_QTY_MAX_APPLIED_VALUE);
    }

    public function isReviseUpdateQty(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_QTY) != 0;
    }

    public function isReviseUpdatePrice(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_PRICE) != 0;
    }

    public function isReviseUpdateTitle(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_TITLE) != 0;
    }

    public function isReviseUpdateDescription(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_DESCRIPTION) != 0;
    }

    public function isReviseUpdateImages(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_IMAGES) != 0;
    }

    public function isReviseUpdateCategories(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_CATEGORIES) != 0;
    }

    public function isReviseUpdateShipping(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_REVISE_UPDATE_SHIPPING) != 0;
    }

    // ---------------------------------------

    public function isRelistMode(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_MODE) != 0;
    }

    public function isRelistFilterUserLock(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_FILTER_USER_LOCK) != 0;
    }

    public function isRelistStatusEnabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_STATUS_ENABLED) != 0;
    }

    public function isRelistIsInStock(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_IS_IN_STOCK) != 0;
    }

    public function isRelistWhenQtyCalculatedHasValue(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_QTY_CALCULATED) != self::QTY_MODE_NONE;
    }

    public function isRelistAdvancedRulesEnabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_ADVANCED_RULES_MODE) != 0
            && !empty($this->getRelistAdvancedRulesFilters());
    }

    public function getRelistAdvancedRulesFilters()
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_ADVANCED_RULES_FILTERS);
    }

    // ---------------------------------------

    public function isStopMode(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_MODE) != 0;
    }

    public function isStopStatusDisabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_STATUS_DISABLED) != 0;
    }

    public function isStopOutOfStock(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_OUT_OFF_STOCK) != 0;
    }

    public function isStopWhenQtyCalculatedHasValue(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_QTY_CALCULATED) != self::QTY_MODE_NONE;
    }

    public function isStopAdvancedRulesEnabled(): bool
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_ADVANCED_RULES_MODE) != 0
            && !empty($this->getStopAdvancedRulesFilters());
    }

    public function getStopAdvancedRulesFilters()
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_ADVANCED_RULES_FILTERS);
    }

    public function getListWhenQtyCalculatedHasValue()
    {
        return $this->getData(SynchronizationResource::COLUMN_LIST_QTY_CALCULATED_VALUE);
    }

    public function getRelistWhenQtyCalculatedHasValueMin()
    {
        return $this->getData(SynchronizationResource::COLUMN_RELIST_QTY_CALCULATED_VALUE);
    }

    public function getStopWhenQtyCalculatedHasValueMin()
    {
        return $this->getData(SynchronizationResource::COLUMN_STOP_QTY_CALCULATED_VALUE);
    }
}
