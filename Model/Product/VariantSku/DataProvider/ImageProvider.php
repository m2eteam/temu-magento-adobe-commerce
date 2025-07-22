<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku\DataProvider;

use M2E\Temu\Model\Product\DataProvider\DataBuilderHelpTrait;
use M2E\Temu\Model\Product\DataProvider\DataBuilderInterface;
use M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Value;

class ImageProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'Image';

    public function getImage(\M2E\Temu\Model\Product\VariantSku $variantSku): Value
    {
        $variantImageSet = $variantSku->getDescriptionTemplateSource()->getImageSet();

        $set = [];

        foreach ($variantImageSet->getAll() as $variantImage) {
            $set[] = new \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Image($variantImage->getUrl());
        }

        return new Value($set);
    }
}
