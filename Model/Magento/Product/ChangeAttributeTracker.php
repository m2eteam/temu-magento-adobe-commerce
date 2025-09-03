<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Magento\Product;

class ChangeAttributeTracker
{
    private const INSTRUCTION_INITIATOR = 'magento_product_change_processor';

    public const INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED = 'magento_product_data_potentially_changed';
    public const INSTRUCTION_TYPE_TITLE_DATA_CHANGED = 'magento_product_title_data_changed';
    public const INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED = 'magento_product_description_data_changed';
    public const INSTRUCTION_TYPE_IMAGES_DATA_CHANGED = 'magento_product_images_data_changed';
    public const INSTRUCTION_TYPE_BULLET_POINTS_DATA_CHANGED = 'magento_product_bullet_points_data_changed';
    public const INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED = 'magento_product_categories_data_changed';
    public const INSTRUCTION_TYPE_ITEM_TAX_CODE_DATA_CHANGED = 'magento_product_item_tax_code_data_changed';

    private \M2E\Temu\Model\Product $listingProduct;
    private \M2E\Temu\Model\InstructionService $instructionService;
    private array $instructionsData = [];

    public function __construct(
        \M2E\Temu\Model\InstructionService $instructionService,
        \M2E\Temu\Model\Product $listingProduct
    ) {
        $this->listingProduct = $listingProduct;
        $this->instructionService = $instructionService;
    }

    public function addInstructionWithPotentiallyChangedType(): void
    {
        $this->addInstruction(self::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED, 100);
    }

    private function addInstruction(string $type, int $priority): void
    {
        $this->instructionsData[$type] = [
            'listing_product_id' => $this->listingProduct->getId(),
            'type' => $type,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => $priority,
        ];
    }

    public function flushInstructions(): void
    {
        if (empty($this->instructionsData)) {
            return;
        }

        $instructionsData = array_values($this->instructionsData);
        $this->instructionService->createBatch($instructionsData);

        $this->instructionsData = [];
    }

    public function getTrackingAttributes(): array
    {
        return [];
    }
}
