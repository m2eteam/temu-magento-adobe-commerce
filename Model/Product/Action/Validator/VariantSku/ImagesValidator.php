<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator\VariantSku;

class ImagesValidator implements ValidatorInterface
{
    public function validate(\M2E\Temu\Model\Product\VariantSku $variant): ?\M2E\Temu\Model\Product\Action\Validator\ValidatorMessage
    {
        $images = $variant->getDataProvider()->getImages()->getValue()->set;

        if (count($images) === 0) {
            return new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                (string)__(
                    'Product Images are missing. To list the Product, ' .
                    'please make sure that the Image settings in the Description policy are correct and the Images ' .
                    'are available in the Magento Product.'
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_IMAGES_MISSING
            );
        }

        foreach ($images as $image) {
            if (!$this->isValidUrl($image->url)) {
                return new \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage(
                    (string)__(
                        'Product Images are invalid. To list the Product, ' .
                        'please make sure that the Image settings in the Description policy are correct and the Images ' .
                        'are available in the Magento Product.'
                    ),
                    \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_IMAGES_INVALID
                );
            }
        }

        return null;
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
