<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy;

abstract class ChangeProcessorAbstract
{
    public const INSTRUCTION_TYPE_QTY_DATA_CHANGED = 'template_qty_data_changed';
    public const INSTRUCTION_TYPE_PRICE_DATA_CHANGED = 'template_price_data_changed';
    public const INSTRUCTION_TYPE_TITLE_DATA_CHANGED = 'template_title_data_changed';
    public const INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED = 'template_description_data_changed';
    public const INSTRUCTION_TYPE_IMAGES_DATA_CHANGED = 'template_images_data_changed';
    public const INSTRUCTION_TYPE_BULLET_POINTS_DATA_CHANGED = 'template_bullet_points_data_changed';
    public const INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED = 'template_categories_data_changed';
    public const INSTRUCTION_TYPE_OTHER_DATA_CHANGED = 'template_other_data_changed';
    public const INSTRUCTION_TYPE_SHIPPING_DATA_CHANGED = 'template_shipping_data_changed';

    private \M2E\Temu\Model\InstructionService $instructionService;

    public function __construct(
        \M2E\Temu\Model\InstructionService $instructionService
    ) {
        $this->instructionService = $instructionService;
    }

    public function process(
        \M2E\Temu\Model\ActiveRecord\Diff $diff,
        array $affectedListingsProductsData
    ): void {
        if (empty($affectedListingsProductsData)) {
            return;
        }

        if (!$diff->isDifferent()) {
            return;
        }

        $listingsProductsInstructionsData = [];

        foreach ($affectedListingsProductsData as $affectedListingProductData) {
            $status = (int)$affectedListingProductData['status'];
            $instructionsData = $this->getInstructionsData($diff, $status);

            foreach ($instructionsData as $instructionData) {
                $listingsProductsInstructionsData[] = [
                    'listing_product_id' => $affectedListingProductData['id'],
                    'type' => $instructionData['type'],
                    'initiator' => $this->getInstructionInitiator(),
                    'priority' => $instructionData['priority'],
                ];
            }
        }

        $this->instructionService->createBatch($listingsProductsInstructionsData);
    }

    abstract protected function getInstructionInitiator(): string;

    // ---------------------------------------

    abstract protected function getInstructionsData(
        \M2E\Temu\Model\ActiveRecord\Diff $diff,
        int $status
    ): array;
}
