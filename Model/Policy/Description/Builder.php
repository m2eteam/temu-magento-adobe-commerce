<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Description;

use M2E\Temu\Model\Policy\Description as DescriptionAlias;

class Builder extends \M2E\Temu\Model\Policy\AbstractBuilder
{
    protected function initData(
        \M2E\Temu\Model\Policy\PolicyInterface $model,
        ?int $id,
        string $title,
        array $rawData
    ): void {

        /** @var \M2E\Temu\Model\Policy\Description $model */

        if (isset($rawData['title_mode'])) {
            $model->setTitleMode((int)$rawData['title_mode']);
        }

        if (isset($rawData['title_template'])) {
            $model->setTitleTemplate($rawData['title_template']);
        }

        if (isset($rawData['description_mode'])) {
            $model->setDescriptionMode((int)$rawData['description_mode']);
        }

        if (isset($rawData['description_template'])) {
            $model->setDescriptionTemplate($rawData['description_template']);
        }

        if (isset($rawData['image_main_mode'])) {
            $model->setImageMainMode((int)$rawData['image_main_mode']);
        }

        if (isset($rawData['image_main_attribute'])) {
            $model->setImageMainAttribute($rawData['image_main_attribute']);
        }

        if (isset($rawData['gallery_images_mode'])) {
            $model->setGalleryImagesMode((int)$rawData['gallery_images_mode']);
        }

        if (isset($rawData['gallery_images_limit'])) {
            $model->setGalleryImagesLimit((int)$rawData['gallery_images_limit']);
        }

        if (isset($rawData['gallery_images_attribute'])) {
            $model->setGalleryImagesAttribute($rawData['gallery_images_attribute']);
        }

        $bulletPoints = [];
        foreach ($rawData['bullet_point'] ?? [] as $bulletPointRaw) {
            if (count($bulletPoints) >= BulletPoint::MAX_COUNT) {
                break;
            }

            $bulletPoint = new BulletPoint(
                (int)$bulletPointRaw['bullet_point_mode'],
                empty($bulletPointRaw['bullet_point_custom_value']) ? null : $bulletPointRaw['bullet_point_custom_value'],
                empty($bulletPointRaw['bullet_point_attribute']) ? null : $bulletPointRaw['bullet_point_attribute']
            );

            if (!$bulletPoint->isConfigured()) {
                continue;
            }

            $bulletPoints[] = $bulletPoint;
        }

        $model->setBulletPoints($bulletPoints);
    }

    // ----------------------------------------

    public function getDefaultData(): array
    {
        return [
            'title_mode' => DescriptionAlias::TITLE_MODE_PRODUCT,
            'title_template' => '',
            'description_mode' => '',
            'description_template' => '',
            'editor_type' => DescriptionAlias::EDITOR_TYPE_SIMPLE,
            'image_main_mode' => DescriptionAlias::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',
            'gallery_images_mode' => DescriptionAlias::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => 0,
            'gallery_images_attribute' => '',
            'variation_images_mode' => DescriptionAlias::VARIATION_IMAGES_MODE_PRODUCT,
            'variation_images_limit' => 1,
            'variation_images_attribute' => '',
            'default_image_url' => '',
            'bullet_points' => [],
        ];
    }
}
