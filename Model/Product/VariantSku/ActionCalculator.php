<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku;

use M2E\Temu\Model\Product\Action\VariantSettings;

class ActionCalculator
{
    private \M2E\Temu\Model\Magento\Product\RuleFactory $ruleFactory;

    public function __construct(\M2E\Temu\Model\Magento\Product\RuleFactory $ruleFactory)
    {
        $this->ruleFactory = $ruleFactory;
    }

    public function process(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Product $product
    ): string {
        if ($variant->isStatusNotListed()) {
            return $this->calculateForNotListed($variant, $product);
        }

        if ($variant->isStatusListed()) {
            return $this->calculateForActive($variant);
        }

        if ($variant->isStatusInactive()) {
            return $this->calculateForInactive($variant);
        }

        throw new \LogicException('Not valid status.');
    }

    private function calculateForActive(
        \M2E\Temu\Model\Product\VariantSku $variant
    ): string {
        if ($this->needStop($variant)) {
            return VariantSettings::ACTION_STOP;
        }

        if ($this->needRevise($variant)) {
            return VariantSettings::ACTION_REVISE;
        }

        return VariantSettings::ACTION_SKIP;
    }

    private function needStop(\M2E\Temu\Model\Product\VariantSku $variant): bool
    {
        $syncPolicy = $variant->getSyncPolicy();
        if (!$syncPolicy->isStopMode()) {
            return false;
        }

        if (
            $syncPolicy->isStopStatusDisabled()
            && !$variant->getMagentoProduct()->isStatusEnabled()
        ) {
            return true;
        }

        if (
            $syncPolicy->isStopOutOfStock()
            && !$variant->getMagentoProduct()->isStockAvailability()
        ) {
            return true;
        }

        if (
            $syncPolicy->isStopWhenQtyCalculatedHasValue()
            && $this->isProductHasCalculatedQtyForStop($variant, (int)$syncPolicy->getStopWhenQtyCalculatedHasValueMin())
        ) {
            return true;
        }

        if (
            $syncPolicy->isStopAdvancedRulesEnabled()
            && $this->isStopAdvancedRuleMet($variant, $syncPolicy)
        ) {
            return true;
        }

        return false;
    }

    private function needRevise(
        \M2E\Temu\Model\Product\VariantSku $variant
    ): bool {
        $syncPolicy = $variant->getSyncPolicy();

        if (
            $syncPolicy->isReviseUpdateQty()
            && $this->isChangedQty($variant, $syncPolicy)
        ) {
            return true;
        }

        if (
            $syncPolicy->isReviseUpdatePrice()
            && $this->isChangedPrice($variant)
        ) {
            return true;
        }

        return false;
    }

    private function isChangedQty(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Policy\Synchronization $syncPolicy
    ): bool {
        $maxAppliedValue = $syncPolicy->getReviseUpdateQtyMaxAppliedValue();

        $productQty = $variant->getDataProvider()->getQty()->getValue();
        $channelQty = $variant->getOnlineQty();

        if (
            $syncPolicy->isReviseUpdateQtyMaxAppliedValueModeOn()
            && $productQty > $maxAppliedValue
            && $channelQty > $maxAppliedValue
        ) {
            return false;
        }

        if ($productQty === $channelQty) {
            return false;
        }

        return true;
    }

    private function isChangedPrice(
        \M2E\Temu\Model\Product\VariantSku $variant
    ): bool {
        return $variant->getOnlinePrice() !== $variant->getDataProvider()->getPrice()->getValue()->price;
    }

    private function calculateForNotListed(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Product $product
    ): string {
        if ($product->isStatusListed()) { // unable to add variant to listed product
            return VariantSettings::ACTION_SKIP;
        }

        $syncPolicy = $variant->getSyncPolicy();
        if (!$syncPolicy->isListMode()) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isListStatusEnabled()
            && !$variant->getMagentoProduct()->isStatusEnabled()
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isListIsInStock()
            && !$variant->getMagentoProduct()->isStockAvailability()
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isListWhenQtyCalculatedHasValue()
            && !$this->isProductHasCalculatedQtyForListRevise($variant, (int)$syncPolicy->getListWhenQtyCalculatedHasValue())
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isListAdvancedRulesEnabled()
            && !$this->isListAdvancedRuleMet($variant, $syncPolicy)
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        return VariantSettings::ACTION_ADD;
    }

    private function calculateForInactive(\M2E\Temu\Model\Product\VariantSku $variant): string
    {
        $syncPolicy = $variant->getSyncPolicy();
        if (!$syncPolicy->isRelistMode()) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isRelistStatusEnabled()
            && !$variant->getMagentoProduct()->isStatusEnabled()
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isRelistIsInStock()
            && !$variant->getMagentoProduct()->isStockAvailability()
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isRelistWhenQtyCalculatedHasValue()
            && !$this->isProductHasCalculatedQtyForListRevise($variant, (int)$syncPolicy->getRelistWhenQtyCalculatedHasValueMin())
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        if (
            $syncPolicy->isRelistAdvancedRulesEnabled()
            && !$this->isRelistAdvancedRuleMet($variant, $syncPolicy)
        ) {
            return VariantSettings::ACTION_SKIP;
        }

        return VariantSettings::ACTION_ADD;
    }

    private function isProductHasCalculatedQtyForListRevise(
        \M2E\Temu\Model\Product\VariantSku $variant,
        int $minQty
    ): bool {
        $productQty = $variant->getDataProvider()->getQty()->getValue();

        return $productQty >= $minQty;
    }

    private function isProductHasCalculatedQtyForStop(
        \M2E\Temu\Model\Product\VariantSku $variant,
        int $minQty
    ): bool {
        $productQty = $variant->getDataProvider()->getQty()->getValue();

        return $productQty <= $minQty;
    }

    private function isListAdvancedRuleMet(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Policy\Synchronization $syncPolicy
    ): bool {
        $ruleModel = $this->ruleFactory
            ->create()
            ->setData(
                [
                    'store_id' => $variant->getListing()->getStoreId(),
                    'prefix' => \M2E\Temu\Model\Policy\Synchronization::LIST_ADVANCED_RULES_PREFIX,
                ],
            );
        $ruleModel->loadFromSerialized($syncPolicy->getListAdvancedRulesFilters());

        if ($ruleModel->validate($variant->getMagentoProduct()->getProduct())) {
            return true;
        }

        return false;
    }

    private function isRelistAdvancedRuleMet(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Policy\Synchronization $syncPolicy
    ): bool {
        $ruleModel = $this->ruleFactory->create();
        $ruleModel->setPrefix(\M2E\Temu\Model\Policy\Synchronization::RELIST_ADVANCED_RULES_PREFIX);
        $ruleModel->setStoreId($variant->getListing()->getStoreId());
        $ruleModel->loadFromSerialized($syncPolicy->getRelistAdvancedRulesFilters());

        if ($ruleModel->validate($variant->getMagentoProduct()->getProduct())) {
            return true;
        }

        return false;
    }

    private function isStopAdvancedRuleMet(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Policy\Synchronization $syncPolicy
    ): bool {
        $ruleModel = $this->ruleFactory
            ->create()
            ->setData(
                [
                    'store_id' => $variant->getListing()->getStoreId(),
                    'prefix' => \M2E\Temu\Model\Policy\Synchronization::STOP_ADVANCED_RULES_PREFIX,
                ],
            );
        $ruleModel->loadFromSerialized($syncPolicy->getStopAdvancedRulesFilters());

        if ($ruleModel->validate($variant->getMagentoProduct()->getProduct())) {
            return true;
        }

        return false;
    }
}
