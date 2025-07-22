<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\DataProvider;

use M2E\Temu\Model\Product\DataProvider\DataBuilderHelpTrait;
use M2E\Temu\Model\Product\DataProvider\DataBuilderInterface;

class IdentifierProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'Identifier';

    private \M2E\Temu\Model\Settings $settings;

    public function __construct(
        \M2E\Temu\Model\Settings $settings
    ) {
        $this->settings = $settings;
    }

    public function getIdentifier(\M2E\Temu\Model\Product\VariantSku $variantSku): string
    {
        $eanAttributeCode = $this->settings->getIdentifierCodeValue();
        $magentoProduct = $variantSku->getMagentoProduct();

        return $magentoProduct->getAttributeValue($eanAttributeCode);
    }
}
