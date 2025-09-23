<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\SellingFormat;

use M2E\Temu\Model\Policy\SellingFormat;

class Builder extends \M2E\Temu\Model\Policy\AbstractBuilder
{
    protected function initData(
        \M2E\Temu\Model\Policy\PolicyInterface $model,
        ?int $id,
        string $title,
        array $rawData
    ): void {
        $data = $rawData;

        if (isset($rawData['listing_type'])) {
            $data['listing_type'] = (int)$rawData['listing_type'];
        }

        if (isset($rawData['listing_is_private'])) {
            $data['listing_is_private'] = (int)(bool)$rawData['listing_is_private'];
        }

        if (isset($rawData['listing_type_attribute'])) {
            $data['listing_type_attribute'] = $rawData['listing_type_attribute'];
        }

        if (isset($rawData['duration_mode'])) {
            $data['duration_mode'] = (int)$rawData['duration_mode'];
        }

        if (isset($rawData['duration_attribute'])) {
            $data['duration_attribute'] = $rawData['duration_attribute'];
        }

        if (isset($rawData['qty_mode'])) {
            $data['qty_mode'] = (int)$rawData['qty_mode'];
        }

        if (isset($rawData['qty_custom_value'])) {
            $data['qty_custom_value'] = (int)$rawData['qty_custom_value'];
        }

        if (isset($rawData['qty_custom_attribute'])) {
            $data['qty_custom_attribute'] = $rawData['qty_custom_attribute'];
        }

        if (isset($rawData['qty_percentage'])) {
            $data['qty_percentage'] = (int)$rawData['qty_percentage'];
        }

        if (isset($rawData['qty_modification_mode'])) {
            $data['qty_modification_mode'] = (int)$rawData['qty_modification_mode'];
        }

        if (isset($rawData['qty_min_posted_value'])) {
            $data['qty_min_posted_value'] = (int)$rawData['qty_min_posted_value'];
        }

        if (isset($rawData['qty_max_posted_value'])) {
            $data['qty_max_posted_value'] = (int)$rawData['qty_max_posted_value'];
        }

        if (isset($rawData['lot_size_mode'])) {
            $data['lot_size_mode'] = (int)$rawData['lot_size_mode'];
        }

        if (isset($rawData['lot_size_custom_value'])) {
            $data['lot_size_custom_value'] = (int)$rawData['lot_size_custom_value'];
        }

        if (isset($rawData['lot_size_attribute'])) {
            $data['lot_size_attribute'] = $rawData['lot_size_attribute'];
        }

        if (isset($rawData['vat_mode'])) {
            $data['vat_mode'] = (int)$rawData['vat_mode'];
        }

        if (isset($rawData['vat_percent'])) {
            $data['vat_percent'] = (float)$rawData['vat_percent'];
        }

        if (isset($rawData['tax_table_mode'])) {
            $data['tax_table_mode'] = (int)$rawData['tax_table_mode'];
        }

        if (isset($rawData['tax_category_mode'])) {
            $data['tax_category_mode'] = (int)$rawData['tax_category_mode'];
        }

        if (isset($rawData['tax_category_value'])) {
            $data['tax_category_value'] = $rawData['tax_category_value'];
        }

        if (isset($rawData['tax_category_attribute'])) {
            $data['tax_category_attribute'] = $rawData['tax_category_attribute'];
        }

        if (isset($rawData['price_variation_mode'])) {
            $data['price_variation_mode'] = (int)$rawData['price_variation_mode'];
        }

        // ---------------------------------------

        if (isset($rawData['fixed_price_mode'])) {
            $data['fixed_price_mode'] = (int)$rawData['fixed_price_mode'];
        }

        $fixedPriceModifierData = $this->getFixedPriceModifierData($rawData);
        if ($fixedPriceModifierData !== null) {
            $data['fixed_price_modifier'] = \M2E\Core\Helper\Json::encode($fixedPriceModifierData);
        }

        if (isset($rawData['fixed_price_custom_attribute'])) {
            $data['fixed_price_custom_attribute'] = $rawData['fixed_price_custom_attribute'];
        }

        if (isset($rawData['reference_link_attribute'])) {
            $value = $rawData['reference_link_attribute'];
            $data['reference_link_attribute'] = empty($value) ? null : $value;
        }

        if (isset($rawData['item_tax_code_mode'])) {
            $itemTaxCodeMode = (int)$rawData['item_tax_code_mode'];
            if ($itemTaxCodeMode === SellingFormat::ITEM_TAX_CODE_MODE_NONE) {
                $data['item_tax_code_attribute'] = null;
                $data['item_tax_code_custom_value'] = null;
            }

            if ($itemTaxCodeMode === SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE) {
                $data['item_tax_code_attribute'] = $rawData['item_tax_code_attribute'];
                $data['item_tax_code_custom_value'] = null;
            }

            if ($itemTaxCodeMode === SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE) {
                $data['item_tax_code_attribute'] = null;
                $data['item_tax_code_custom_value'] = $rawData['item_tax_code_custom_value'];
            }
            $data['item_tax_code_mode'] = $itemTaxCodeMode;
        }

        // ---------------------------------------

        if (isset($rawData['start_price_mode'])) {
            $data['start_price_mode'] = (int)$rawData['start_price_mode'];
        }

        if (isset($rawData['start_price_coefficient'], $rawData['start_price_coefficient_mode'])) {
            $data['start_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $rawData['start_price_coefficient'],
                $rawData['start_price_coefficient_mode']
            );
        }

        if (isset($rawData['start_price_custom_attribute'])) {
            $data['start_price_custom_attribute'] = $rawData['start_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($rawData['reserve_price_mode'])) {
            $data['reserve_price_mode'] = (int)$rawData['reserve_price_mode'];
        }

        if (isset($rawData['reserve_price_coefficient'], $rawData['reserve_price_coefficient_mode'])) {
            $data['reserve_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $rawData['reserve_price_coefficient'],
                $rawData['reserve_price_coefficient_mode']
            );
        }

        if (isset($rawData['reserve_price_custom_attribute'])) {
            $data['reserve_price_custom_attribute'] = $rawData['reserve_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($rawData['price_discount_stp_mode'])) {
            $data['price_discount_stp_mode'] = (int)$rawData['price_discount_stp_mode'];
        }

        if (isset($rawData['price_discount_stp_attribute'])) {
            $data['price_discount_stp_attribute'] = $rawData['price_discount_stp_attribute'];
        }

        if (isset($rawData['price_discount_stp_type'])) {
            $data['price_discount_stp_type'] = (int)$rawData['price_discount_stp_type'];
        }

        // ---------------------------------------

        if (isset($rawData['price_discount_map_mode'])) {
            $data['price_discount_map_mode'] = (int)$rawData['price_discount_map_mode'];
        }

        if (isset($rawData['price_discount_map_attribute'])) {
            $data['price_discount_map_attribute'] = $rawData['price_discount_map_attribute'];
        }

        if (isset($rawData['price_discount_map_exposure_type'])) {
            $data['price_discount_map_exposure_type'] = (int)$rawData['price_discount_map_exposure_type'];
        }

        if (isset($rawData['restricted_to_business'])) {
            $data['restricted_to_business'] = (int)$rawData['restricted_to_business'];
        }

        // ---------------------------------------

        if (isset($rawData['best_offer_mode'])) {
            $data['best_offer_mode'] = (int)$rawData['best_offer_mode'];
        }

        if (isset($rawData['best_offer_accept_mode'])) {
            $data['best_offer_accept_mode'] = (int)$rawData['best_offer_accept_mode'];
        }

        if (isset($rawData['best_offer_accept_value'])) {
            $data['best_offer_accept_value'] = $rawData['best_offer_accept_value'];
        }

        if (isset($rawData['best_offer_accept_attribute'])) {
            $data['best_offer_accept_attribute'] = $rawData['best_offer_accept_attribute'];
        }

        if (isset($rawData['best_offer_reject_mode'])) {
            $data['best_offer_reject_mode'] = (int)$rawData['best_offer_reject_mode'];
        }

        if (isset($rawData['best_offer_reject_value'])) {
            $data['best_offer_reject_value'] = $rawData['best_offer_reject_value'];
        }

        if (isset($rawData['best_offer_reject_attribute'])) {
            $data['best_offer_reject_attribute'] = $rawData['best_offer_reject_attribute'];
        }

        if (isset($rawData['paypal_immediate_payment'])) {
            $data['paypal_immediate_payment'] = $rawData['paypal_immediate_payment'];
        }

        if (isset($rawData['ignore_variations'])) {
            $data['ignore_variations'] = (int)$rawData['ignore_variations'];
        }

        /** @var \M2E\Temu\Model\Policy\SellingFormat $model */
        $model->addData($data);
    }

    /**
     * @param $priceCoefficient
     * @param $priceCoefficientMode
     *
     * @return string
     */
    private function getFormattedPriceCoefficient($priceCoefficient, $priceCoefficientMode): string
    {
        if ($priceCoefficientMode == SellingFormat::PRICE_COEFFICIENT_NONE) {
            return '';
        }

        $isCoefficientModeDecrease =
            $priceCoefficientMode == SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE ||
            $priceCoefficientMode == SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE;

        $isCoefficientModePercentage =
            $priceCoefficientMode == SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE ||
            $priceCoefficientMode == SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE;

        $sign = $isCoefficientModeDecrease ? '-' : '+';
        $measuringSystem = $isCoefficientModePercentage ? '%' : '';

        return $sign . $priceCoefficient . $measuringSystem;
    }

    private function getFixedPriceModifierData(array $rawData): ?array
    {
        if (
            !empty($rawData['fixed_price_modifier_mode'])
            && is_array($rawData['fixed_price_modifier_mode'])
        ) {
            $fixedPriceModifierData = [];
            foreach ($rawData['fixed_price_modifier_mode'] as $key => $fixedPriceModifierMode) {
                if (
                    !isset($rawData['fixed_price_modifier_value'][$key])
                    || !is_string($rawData['fixed_price_modifier_value'][$key])
                    || !isset($rawData['fixed_price_modifier_attribute'][$key])
                    || !is_string($rawData['fixed_price_modifier_attribute'][$key])
                ) {
                    continue;
                }

                if ($fixedPriceModifierMode == SellingFormat::PRICE_COEFFICIENT_ATTRIBUTE) {
                    $fixedPriceModifierData[] = [
                        'mode' => $fixedPriceModifierMode,
                        'attribute_code' => $rawData['fixed_price_modifier_attribute'][$key],
                    ];
                } else {
                    $fixedPriceModifierData[] = [
                        'mode' => $fixedPriceModifierMode,
                        'value' => $rawData['fixed_price_modifier_value'][$key],
                    ];
                }
            }

            return $fixedPriceModifierData;
        }

        return null;
    }

    public function getDefaultData(): array
    {
        return [

            'qty_mode' => SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => SellingFormat::QTY_MODIFICATION_MODE_OFF,
            'qty_min_posted_value' => SellingFormat::QTY_MIN_POSTED_DEFAULT_VALUE,
            'qty_max_posted_value' => SellingFormat::QTY_MAX_POSTED_DEFAULT_VALUE,

            'fixed_price_mode' => SellingFormat::PRICE_MODE_PRODUCT,
            'fixed_price_modifier' => '[]',
            'fixed_price_custom_attribute' => '',

            'reference_link_attribute' => null,

            'item_tax_code_mode' => SellingFormat::ITEM_TAX_CODE_MODE_NONE,
            'item_tax_code_attribute' => null,
            'item_tax_code_custom_value' => null,
        ];
    }
}
