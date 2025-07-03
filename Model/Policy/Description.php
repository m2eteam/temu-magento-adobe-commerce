<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy;

use M2E\Temu\Model\ResourceModel\Policy\Description as DescriptionResource;
use M2E\Temu\Model\Policy\Description\BulletPoint;

class Description extends \M2E\Temu\Model\ActiveRecord\AbstractModel implements PolicyInterface
{
    public const IMAGE_MAIN_MODE_ATTRIBUTE = 2;
    public const VARIATION_IMAGES_MODE_ATTRIBUTE = 2;
    public const VARIATION_IMAGES_MODE_PRODUCT = 1;
    public const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;
    public const TITLE_MODE_PRODUCT = 0;
    public const GALLERY_IMAGES_MODE_NONE = 0;
    public const EDITOR_TYPE_TINYMCE = 1;
    public const INSTRUCTION_TYPE_MAGENTO_STATIC_BLOCK_IN_DESCRIPTION_CHANGED = 'magento_static_block_in_description_changed';
    public const TITLE_MODE_CUSTOM = 1;
    public const GALLERY_IMAGES_MODE_PRODUCT = 1;
    public const DESCRIPTION_MODE_CUSTOM = 2;
    public const EDITOR_TYPE_SIMPLE = 0;
    public const VARIATION_IMAGES_MODE_NONE = 0;
    public const DESCRIPTION_MODE_PRODUCT = 0;
    public const DESCRIPTION_MODE_SHORT = 1;
    public const IMAGE_MAIN_MODE_PRODUCT = 1;

    /** @var \M2E\Temu\Model\Policy\Description\Source[] */
    private array $descriptionSourceModels = [];
    private Description\SourceFactory $sourceFactory;

    public function __construct(
        Description\SourceFactory $sourceFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->sourceFactory = $sourceFactory;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\M2E\Temu\Model\ResourceModel\Policy\Description::class);
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return \M2E\Temu\Model\Policy\Manager::TEMPLATE_DESCRIPTION;
    }

    public function setTitle(string $title): void
    {
        $this->setData(DescriptionResource::COLUMN_TITLE, $title);
    }

    public function getTitle(): string
    {
        return $this->getData(DescriptionResource::COLUMN_TITLE);
    }

    // ----------------------------------------

    public function setTitleMode(int $mode): self
    {
        $this->setData(DescriptionResource::COLUMN_TITLE_MODE, $mode);

        return $this;
    }

    public function getTitleMode(): int
    {
        return (int)$this->getData(DescriptionResource::COLUMN_TITLE_MODE);
    }

    public function setTitleTemplate(string $template): self
    {
        $this->setData(DescriptionResource::COLUMN_TITLE_TEMPLATE, $template);

        return $this;
    }

    public function getTitleSource(): array
    {
        return [
            'mode' => $this->getTitleMode(),
            'template' => $this->getData(DescriptionResource::COLUMN_TITLE_TEMPLATE),
        ];
    }

    public function getTitleTrackedAttributes(): array
    {
        $attributes = [];
        $src = $this->getTitleSource();

        if ($src['mode'] == self::TITLE_MODE_PRODUCT) {
            $attributes[] = 'name';
        } else {
            $match = [];
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    public function setDescriptionMode(int $mode): self
    {
        $this->setData(DescriptionResource::COLUMN_DESCRIPTION_MODE, $mode);

        return $this;
    }

    public function getDescriptionMode(): int
    {
        return (int)$this->getData(DescriptionResource::COLUMN_DESCRIPTION_MODE);
    }

    public function setDescriptionTemplate(string $template): self
    {
        $this->setData(DescriptionResource::COLUMN_DESCRIPTION_TEMPLATE, $template);

        return $this;
    }

    public function getDescriptionSource(): array
    {
        return [
            'mode' => $this->getDescriptionMode(),
            'template' => $this->getData(DescriptionResource::COLUMN_DESCRIPTION_TEMPLATE),
        ];
    }

    public function getDescriptionTrackedAttributes(): array
    {
        $attributes = [];
        $src = $this->getDescriptionSource();

        if ($src['mode'] == self::DESCRIPTION_MODE_PRODUCT) {
            $attributes[] = 'description';
        } elseif ($src['mode'] == self::DESCRIPTION_MODE_SHORT) {
            $attributes[] = 'short_description';
        } else {
            preg_match_all('/#([a-zA-Z_0-9]+?)#|#(image|media_gallery)\[.*\]#+?/', $src['template'], $match);
            !empty($match[0]) && $attributes = array_filter(array_merge($match[1], $match[2]));
        }

        return $attributes;
    }

    // ---------------------------------------

    public function setImageMainMode(int $mode): self
    {
        $this->setData(DescriptionResource::COLUMN_IMAGE_MAIN_MODE, $mode);

        return $this;
    }

    public function getImageMainMode(): int
    {
        return (int)$this->getData(DescriptionResource::COLUMN_IMAGE_MAIN_MODE);
    }

    public function isImageMainModeProduct(): bool
    {
        return $this->getImageMainMode() === self::IMAGE_MAIN_MODE_PRODUCT;
    }

    public function isImageMainModeAttribute(): bool
    {
        return $this->getImageMainMode() === self::IMAGE_MAIN_MODE_ATTRIBUTE;
    }

    public function setImageMainAttribute(string $attribute): self
    {
        $this->setData(DescriptionResource::COLUMN_IMAGE_MAIN_ATTRIBUTE, $attribute);

        return $this;
    }

    public function getImageMainSource(): array
    {
        return [
            'mode' => $this->getImageMainMode(),
            'attribute' => $this->getData(DescriptionResource::COLUMN_IMAGE_MAIN_ATTRIBUTE),
        ];
    }

    public function getImageMainTrackedAttributes(): array
    {
        $attributes = [];
        $src = $this->getImageMainSource();

        if ($src['mode'] == self::IMAGE_MAIN_MODE_PRODUCT) {
            $attributes[] = 'image';
        } elseif ($src['mode'] == self::IMAGE_MAIN_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function setGalleryImagesMode(int $mode): self
    {
        $this->setData(DescriptionResource::COLUMN_GALLERY_IMAGES_MODE, $mode);

        return $this;
    }

    public function getGalleryImagesMode(): int
    {
        return (int)$this->getData(DescriptionResource::COLUMN_GALLERY_IMAGES_MODE);
    }

    public function isGalleryImagesModeNone(): bool
    {
        return $this->getGalleryImagesMode() === self::GALLERY_IMAGES_MODE_NONE;
    }

    public function isGalleryImagesModeProduct(): bool
    {
        return $this->getGalleryImagesMode() === self::GALLERY_IMAGES_MODE_PRODUCT;
    }

    public function isGalleryImagesModeAttribute(): bool
    {
        return $this->getGalleryImagesMode() === self::GALLERY_IMAGES_MODE_ATTRIBUTE;
    }

    public function setGalleryImagesAttribute(string $attribute): self
    {
        $this->setData(DescriptionResource::COLUMN_GALLERY_IMAGES_ATTRIBUTE, $attribute);

        return $this;
    }

    public function setGalleryImagesLimit(int $limit): self
    {
        $this->setData(DescriptionResource::COLUMN_GALLERY_IMAGES_LIMIT, $limit);

        return $this;
    }

    public function getGalleryImagesSource(): array
    {
        return [
            'mode' => $this->getGalleryImagesMode(),
            'attribute' => $this->getData(DescriptionResource::COLUMN_GALLERY_IMAGES_ATTRIBUTE),
            'limit' => $this->getData(DescriptionResource::COLUMN_GALLERY_IMAGES_LIMIT),
        ];
    }

    public function getGalleryImagesTrackedAttributes(): array
    {
        $attributes = [];
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } elseif ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    /**
     * @param Description\BulletPoint[] $bulletPoints
     *
     * @return self
     */
    public function setBulletPoints(array $bulletPoints): self
    {
        $data = [];
        foreach ($bulletPoints as $bulletPoint) {
            $data[] = [
                'bullet_point_mode' => $bulletPoint->getMode(),
                'bullet_point_custom_value' => $bulletPoint->getCustomValue(),
                'bullet_point_attribute' => $bulletPoint->getAttribute(),
            ];
        }

        $this->setData(DescriptionResource::COLUMN_BULLET_POINTS, json_encode($data));

        return $this;
    }

    /**
     * @return \M2E\Temu\Model\Policy\Description\BulletPoint[]
     */
    public function getBulletPoints(): array
    {
        $bulletPointsJson = $this->getData(DescriptionResource::COLUMN_BULLET_POINTS);
        if (empty($bulletPointsJson)) {
            return [];
        }

        $decoded = (array)json_decode($bulletPointsJson, true);

        $result = [];

        foreach ($decoded as $item) {
            $result[] = new BulletPoint(
                $item['bullet_point_mode'],
                $item['bullet_point_custom_value'],
                $item['bullet_point_attribute'],
            );
        }

        return $result;
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    // ----------------------------------------

    public function getSource(
        \M2E\Temu\Model\Magento\Product $magentoProduct
    ): Description\Source {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSourceModels[$productId])) {
            return $this->descriptionSourceModels[$productId];
        }

        $this->descriptionSourceModels[$productId] = $this->sourceFactory->create();
        $this->descriptionSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionSourceModels[$productId]->setDescriptionPolicy($this);

        return $this->descriptionSourceModels[$productId];
    }
}
