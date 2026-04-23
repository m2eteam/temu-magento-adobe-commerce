<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action;

class VariantSettingsBuilder
{
    private bool $needForceRevise;
    private array $data = [];

    public function __construct(bool $needForceRevise)
    {
        $this->needForceRevise = $needForceRevise;
    }

    public function add(int $variantId, string $action, int $currentVariantStatus): void
    {
        $this->validateAction($action);

        $this->data[$variantId] = [
            'action' => $action,
            'current_status' => $currentVariantStatus,
        ];
    }

    public function build(): VariantSettings
    {
        if (empty($this->data)) {
            throw new \LogicException('Unable to build variant settings.');
        }

        $variantsData = $this->data;

        $this->data = [];

        $actions = $this->collectActions(array_column($variantsData, 'action'));
        if (
            $this->isAllSkip($actions)
            && !$this->needForceRevise
        ) {
            return $this->variantSettings($variantsData);
        }

        $variantsData = $this->recalculateActions($variantsData);

        return $this->variantSettings($variantsData);
    }

    private function isAllSkip(array $actions): bool
    {
        return count($actions) === 1 && $actions[0] === VariantSettings::ACTION_SKIP;
    }

    private function recalculateActions(array $variantData): array
    {
        $result = [];
        foreach ($variantData as $variantId => $data) {
            $action = $data['action'];
            $currentStatus = $data['current_status'];

            if ($action === VariantSettings::ACTION_SKIP) {
                if ($currentStatus === \M2E\Temu\Model\Product::STATUS_LISTED) {
                    $action = VariantSettings::ACTION_REVISE;
                }

                if ($currentStatus === \M2E\Temu\Model\Product::STATUS_INACTIVE) {
                    $action = VariantSettings::ACTION_STOP;
                }
            }

            $result[$variantId] = [
                'action' => $action,
                'current_status' => $currentStatus,
            ];
        }

        return $result;
    }

    private function variantSettings(array $variantData): VariantSettings
    {
        $result = new VariantSettings();
        foreach ($variantData as $variantId => $data) {
            $result->add($variantId, $data['action']);
        }

        return $result;
    }

    /**
     * @param array $actions
     *
     * @return string[]
     */
    private function collectActions(array $actions): array
    {
        return array_unique($actions);
    }

    // ----------------------------------------

    private function validateAction(string $action): void
    {
        if (
            !in_array(
                $action,
                [
                    VariantSettings::ACTION_ADD,
                    VariantSettings::ACTION_REVISE,
                    VariantSettings::ACTION_STOP,
                    VariantSettings::ACTION_SKIP,
                ],
            )
        ) {
            throw new \M2E\Temu\Model\Exception\Logic(sprintf('Action %s not valid for variant.', $action));
        }
    }
}
