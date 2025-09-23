<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard;

use M2E\Temu\Model\Listing\Wizard;

class StepDeclarationCollectionFactory
{
    public const STEP_SELECT_PRODUCT_SOURCE = 'products-source';
    public const STEP_SELECT_PRODUCTS = 'select-products';
    public const STEP_POLICY_SETTINGS = 'policy-settings';
    public const STEP_SETTINGS = 'settings';
    public const STEP_SELECT_CATEGORY_MODE = 'category-mode';
    public const STEP_SELECT_CATEGORY = 'select-category';
    public const STEP_CATEGORY_VALIDATION = 'category-validation';
    public const STEP_REVIEW = 'review';

    private static array $steps = [
        Wizard::TYPE_GENERAL => [
            [
                'nick' => self::STEP_SELECT_PRODUCT_SOURCE,
                'route' => '*/listing_wizard_productSource/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_SELECT_PRODUCTS,
                'route' => '*/listing_wizard_product/view',
                'back_handler' => \M2E\Temu\Model\Listing\Wizard\Step\BackHandler\Products::class,
            ],
            [
                'nick' => self::STEP_SETTINGS,
                'route' => '*/listing_wizard_settings/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_POLICY_SETTINGS,
                'route' => '*/listing_wizard_policy/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_SELECT_CATEGORY_MODE,
                'route' => '*/listing_wizard_category/modeView',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_SELECT_CATEGORY,
                'route' => '*/listing_wizard_category/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_CATEGORY_VALIDATION,
                'route' => '*/listing_wizard_categoryValidation/view',
                'back_handler' => \M2E\Temu\Model\Listing\Wizard\Step\BackHandler\CategoryValidation::class,
            ],
            [
                'nick' => self::STEP_REVIEW,
                'route' => '*/listing_wizard_review/view',
                'back_handler' => null,
            ],
        ],
        Wizard::TYPE_UNMANAGED => [
            [
                'nick' => self::STEP_REVIEW,
                'route' => '*/listing_wizard_review/view/type/unmanaged',
                'back_handler' => null,
            ],
        ],
    ];

    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $type): StepDeclarationCollection
    {
        $this->validateType($type);

        $steps = [];
        foreach (self::$steps[$type] as $stepData) {
            $steps[] = new StepDeclaration($stepData['nick'], $stepData['route'], $stepData['back_handler']);
        }

        return $this->objectManager->create(StepDeclarationCollection::class, ['steps' => $steps]);
    }

    private function validateType(string $type): void
    {
        if (!in_array($type, [Wizard::TYPE_GENERAL, Wizard::TYPE_UNMANAGED])) {
            throw new \LogicException(sprintf('Listing Wizard type "%s" is not valid.', $type));
        }
    }
}
