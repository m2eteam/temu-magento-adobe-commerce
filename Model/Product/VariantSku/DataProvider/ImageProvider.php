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
        $set = $this->findImagesFromVariant($variantSku);
        if (empty($set)) {
            $set = $this->findImagesFromProduct($variantSku);
        }

        return new Value($set);
    }

    private function findImagesFromVariant(\M2E\Temu\Model\Product\VariantSku $variantSku): array
    {
        $variantImageSet = $variantSku->getDescriptionTemplateSource()->getImageSet();

        $set = [];
        foreach ($variantImageSet->getAll() as $variantImage) {
            $set[] = new \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Image($variantImage->getUrl());
        }

        return $set;
    }

    private function findImagesFromProduct(\M2E\Temu\Model\Product\VariantSku $variantSku): array
    {
        $productMainImage = $variantSku->getProduct()->getDescriptionTemplateSource()->getMainImage();
        if ($productMainImage === null) {
            return [];
        }

        return [
            new \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Image($productMainImage->getUrl()),
        ];
    }
}
