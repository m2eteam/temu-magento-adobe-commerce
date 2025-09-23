<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\SellingFormat;

class Diff extends \M2E\Temu\Model\ActiveRecord\Diff
{
    public function isDifferent(): bool
    {
        return $this->isQtyDifferent()
            || $this->isPriceDifferent()
            || $this->isItemTaxCodeAttributeDifferent();
    }

    public function isQtyDifferent(): bool
    {
        $keys = [
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_MODE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_CUSTOM_VALUE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_CUSTOM_ATTRIBUTE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_PERCENTAGE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_MODIFICATION_MODE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_MIN_POSTED_VALUE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_QTY_MAX_POSTED_VALUE,
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isPriceDifferent(): bool
    {
        $keys = [
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_FIXED_PRICE_MODE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_FIXED_PRICE_MODIFIER,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_FIXED_PRICE_CUSTOM_ATTRIBUTE,
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isItemTaxCodeAttributeDifferent(): bool
    {
        $keys = [
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_ITEM_TAX_CODE_MODE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_ITEM_TAX_CODE_ATTRIBUTE,
            \M2E\Temu\Model\ResourceModel\Policy\SellingFormat::COLUMN_ITEM_TAX_CODE_CUSTOM_VALUE,
        ];

        return $this->isSettingsDifferent($keys);
    }
}
