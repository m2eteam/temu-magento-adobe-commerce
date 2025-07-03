<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Synchronization;

use M2E\Temu\Model\Policy\Synchronization;

class Builder extends \M2E\Temu\Model\Policy\AbstractBuilder
{
    private \Magento\Framework\App\RequestInterface $request;
    private \M2E\Temu\Model\Magento\Product\RuleFactory $ruleFactory;
    /** @var mixed */
    private array $rawData = [];

    public function __construct(
        \M2E\Temu\Model\Magento\Product\RuleFactory $ruleFactory,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
        $this->ruleFactory = $ruleFactory;
    }

    protected function initData(
        \M2E\Temu\Model\Policy\PolicyInterface $model,
        ?int $id,
        string $title,
        array $rawData
    ): void {
        $this->rawData = $rawData;

        $data = array_merge(
            $rawData,
            $this->prepareListData(),
            $this->prepareReviseData(),
            $this->prepareRelistData(),
            $this->prepareStopData()
        );

        /** @var \M2E\Temu\Model\Policy\Synchronization $model */
        $model->addData($data);
    }

    // ---------------------------------------

    private function prepareListData(): array
    {
        $data = [];

        if (isset($this->rawData['list_mode'])) {
            $data['list_mode'] = (int)$this->rawData['list_mode'];
        }

        if (isset($this->rawData['list_status_enabled'])) {
            $data['list_status_enabled'] = (int)$this->rawData['list_status_enabled'];
        }

        if (isset($this->rawData['list_is_in_stock'])) {
            $data['list_is_in_stock'] = (int)$this->rawData['list_is_in_stock'];
        }

        if (isset($this->rawData['list_qty_calculated'])) {
            $data['list_qty_calculated'] = (int)$this->rawData['list_qty_calculated'];
        }

        if (isset($this->rawData['list_qty_calculated_value'])) {
            $data['list_qty_calculated_value'] = (int)$this->rawData['list_qty_calculated_value'];
        }

        if (isset($this->rawData['list_advanced_rules_mode'])) {
            $data['list_advanced_rules_mode'] = (int)$this->rawData['list_advanced_rules_mode'];
        }

        $data['list_advanced_rules_filters'] = $this->getRuleData(
            Synchronization::LIST_ADVANCED_RULES_PREFIX
        );

        return $data;
    }

    private function prepareReviseData(): array
    {
        $data = [
            'revise_update_qty' => 1,
        ];

        $key = 'revise_update_qty_max_applied_value_mode';
        if (isset($this->rawData[$key])) {
            $data[$key] = (int)$this->rawData[$key];
        }

        if (isset($this->rawData['revise_update_qty_max_applied_value'])) {
            $data['revise_update_qty_max_applied_value'] = (int)$this->rawData['revise_update_qty_max_applied_value'];
        }

        if (isset($this->rawData['revise_update_price'])) {
            $data['revise_update_price'] = (int)$this->rawData['revise_update_price'];
        }

        if (isset($this->rawData['revise_update_title'])) {
            $data['revise_update_title'] = (int)$this->rawData['revise_update_title'];
        }

        if (isset($this->rawData['revise_update_description'])) {
            $data['revise_update_description'] = (int)$this->rawData['revise_update_description'];
        }

        if (isset($this->rawData['revise_update_images'])) {
            $data['revise_update_images'] = (int)$this->rawData['revise_update_images'];
        }

        if (isset($this->rawData['revise_update_categories'])) {
            $data['revise_update_categories'] = (int)$this->rawData['revise_update_categories'];
        }

        if (isset($this->rawData['revise_update_shipping'])) {
            $data['revise_update_shipping'] = (int)$this->rawData['revise_update_shipping'];
        }

        if (isset($this->rawData['revise_update_other'])) {
            $data['revise_update_other'] = (int)$this->rawData['revise_update_other'];
        }

        return $data;
    }

    private function prepareRelistData(): array
    {
        $data = [];

        if (isset($this->rawData['relist_mode'])) {
            $data['relist_mode'] = (int)$this->rawData['relist_mode'];
        }

        if (isset($this->rawData['relist_filter_user_lock'])) {
            $data['relist_filter_user_lock'] = (int)$this->rawData['relist_filter_user_lock'];
        }

        if (isset($this->rawData['relist_status_enabled'])) {
            $data['relist_status_enabled'] = (int)$this->rawData['relist_status_enabled'];
        }

        if (isset($this->rawData['relist_is_in_stock'])) {
            $data['relist_is_in_stock'] = (int)$this->rawData['relist_is_in_stock'];
        }

        if (isset($this->rawData['relist_qty_calculated'])) {
            $data['relist_qty_calculated'] = (int)$this->rawData['relist_qty_calculated'];
        }

        if (isset($this->rawData['relist_qty_calculated_value'])) {
            $data['relist_qty_calculated_value'] = (int)$this->rawData['relist_qty_calculated_value'];
        }

        if (isset($this->rawData['relist_advanced_rules_mode'])) {
            $data['relist_advanced_rules_mode'] = (int)$this->rawData['relist_advanced_rules_mode'];
        }

        $data['relist_advanced_rules_filters'] = $this->getRuleData(
            Synchronization::RELIST_ADVANCED_RULES_PREFIX
        );

        return $data;
    }

    private function prepareStopData(): array
    {
        $data = [];

        if (isset($this->rawData['stop_mode'])) {
            $data['stop_mode'] = (int)$this->rawData['stop_mode'];
        }

        if (isset($this->rawData['stop_status_disabled'])) {
            $data['stop_status_disabled'] = (int)$this->rawData['stop_status_disabled'];
        }

        if (isset($this->rawData['stop_out_off_stock'])) {
            $data['stop_out_off_stock'] = (int)$this->rawData['stop_out_off_stock'];
        }

        if (isset($this->rawData['stop_qty_calculated'])) {
            $data['stop_qty_calculated'] = (int)$this->rawData['stop_qty_calculated'];
        }

        if (isset($this->rawData['stop_qty_calculated_value'])) {
            $data['stop_qty_calculated_value'] = (int)$this->rawData['stop_qty_calculated_value'];
        }

        if (isset($this->rawData['stop_advanced_rules_mode'])) {
            $data['stop_advanced_rules_mode'] = (int)$this->rawData['stop_advanced_rules_mode'];
        }

        $data['stop_advanced_rules_filters'] = $this->getRuleData(
            Synchronization::STOP_ADVANCED_RULES_PREFIX
        );

        return $data;
    }

    private function getRuleData($rulePrefix): ?string
    {
        $post = $this->request->getParams();

        if (empty($post['rule'][$rulePrefix])) {
            return null;
        }

        $ruleModel = $this->ruleFactory->create()->setData(
            ['prefix' => $rulePrefix]
        );

        return $ruleModel->getSerializedFromPost($post);
    }

    // ----------------------------------------

    public function getDefaultData(): array
    {
        return [
            // list
            'list_mode' => 1,
            'list_status_enabled' => 1,
            'list_is_in_stock' => 1,

            'list_qty_calculated' => Synchronization::QTY_MODE_YES,
            'list_qty_calculated_value' => '1',

            'list_advanced_rules_mode' => 0,
            'list_advanced_rules_filters' => null,

            // relist
            'relist_mode' => 1,
            'relist_filter_user_lock' => 1,
            'relist_status_enabled' => 1,
            'relist_is_in_stock' => 1,

            'relist_qty_calculated' => Synchronization::QTY_MODE_YES,
            'relist_qty_calculated_value' => '1',

            'relist_advanced_rules_mode' => 0,
            'relist_advanced_rules_filters' => null,

            // revise
            'revise_update_qty' => 1,
            'revise_update_qty_max_applied_value_mode' => 1,
            'revise_update_qty_max_applied_value' => 5,
            'revise_update_price' => 1,
            'revise_update_title' => 0,
            'revise_update_description' => 0,
            'revise_update_images' => 0,
            'revise_update_categories' => 0,
            'revise_update_shipping' => 0,
            'revise_update_other' => 0,

            // stop
            'stop_mode' => 1,

            'stop_status_disabled' => 1,
            'stop_out_off_stock' => 1,

            'stop_qty_calculated' => Synchronization::QTY_MODE_YES,
            'stop_qty_calculated_value' => '0',

            'stop_advanced_rules_mode' => 0,
            'stop_advanced_rules_filters' => null,
        ];
    }
}
