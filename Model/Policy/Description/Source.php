<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Description;

use M2E\Temu\Model\Policy\Description;

class Source
{
    public const GALLERY_IMAGES_COUNT_MAX = 10;

    private ?\M2E\Temu\Model\Magento\Product $magentoProduct = null;
    private ?\M2E\Temu\Model\Policy\Description $descriptionTemplateModel = null;
    private ?\M2E\Temu\Model\Magento\Product\Image\Set $imagesSet = null;

    private \M2E\Temu\Model\Product\Description\TemplateParser $templateParser;
    private \Magento\Email\Model\Template\Filter $emailTemplateFilter;
    private \M2E\Temu\Model\Magento\Product\ImageFactory $magentoProductImageFactory;

    public function __construct(
        \M2E\Temu\Model\Product\Description\TemplateParser $templateParser,
        \Magento\Email\Model\Template\Filter $emailTemplateFilter,
        \M2E\Temu\Model\Magento\Product\ImageFactory $magentoProductImageFactory
    ) {
        $this->templateParser = $templateParser;
        $this->emailTemplateFilter = $emailTemplateFilter;
        $this->magentoProductImageFactory = $magentoProductImageFactory;
    }

    public function setMagentoProduct(\M2E\Temu\Model\Magento\Product $magentoProduct): self
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    public function getMagentoProduct(): ?\M2E\Temu\Model\Magento\Product
    {
        return $this->magentoProduct;
    }

    public function setDescriptionPolicy(\M2E\Temu\Model\Policy\Description $instance): self
    {
        $this->descriptionTemplateModel = $instance;

        return $this;
    }

    public function getDescriptionTemplate(): ?\M2E\Temu\Model\Policy\Description
    {
        return $this->descriptionTemplateModel;
    }

    public function getTitle(): string
    {
        $src = $this->getDescriptionTemplate()->getTitleSource();

        switch ($src['mode']) {
            case Description::TITLE_MODE_CUSTOM:
                $title = $this->templateParser->parseTemplate($src['template'], $this->getMagentoProduct());
                break;

            default:
                $title = $this->getMagentoProduct()->getName();
                break;
        }

        return $title;
    }

    /**
     * @throws \M2E\Temu\Model\Exception|\Magento\Framework\Exception\FileSystemException
     */
    public function getDescription(): string
    {
        $src = $this->getDescriptionTemplate()->getDescriptionSource();

        switch ($src['mode']) {
            case Description::DESCRIPTION_MODE_PRODUCT:
                /** @psalm-suppress UndefinedMagicMethod */
                $description = (string)$this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case Description::DESCRIPTION_MODE_SHORT:
                /** @psalm-suppress UndefinedMagicMethod */
                $description = (string)$this->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case Description::DESCRIPTION_MODE_CUSTOM:
                $description = $this->templateParser->parseTemplate(
                    $src['template'],
                    $this->getMagentoProduct()
                );
                break;

            default:
                $description = '';
                break;
        }

        return str_replace(['<![CDATA[', ']]>'], '', $description);
    }

    public function getImageSet(): \M2E\Temu\Model\Magento\Product\Image\Set
    {
        if ($this->imagesSet !== null) {
            return $this->imagesSet;
        }

        $set = new \M2E\Temu\Model\Magento\Product\Image\Set();

        $mainImage = $this->getMainImage();
        if ($mainImage !== null) {
            $set->add($mainImage);
        }

        $images = $this->getGalleryImages();
        foreach ($images as $image) {
            $set->add($image);
        }

        return $this->imagesSet = $set;
    }

    public function getMainImage(): ?\M2E\Temu\Model\Magento\Product\Image
    {
        $image = null;

        if ($this->getDescriptionTemplate()->isImageMainModeProduct()) {
            $image = $this->getMagentoProduct()->getImage();
        }

        if ($this->getDescriptionTemplate()->isImageMainModeAttribute()) {
            $src = $this->getDescriptionTemplate()->getImageMainSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        return $image;
    }

    /**
     * @return \M2E\Temu\Model\Magento\Product\Image[]
     */
    public function getGalleryImages(): array
    {
        if ($this->getDescriptionTemplate()->isGalleryImagesModeNone()) {
            return [];
        }

        $galleryImages = [];
        $gallerySource = $this->getDescriptionTemplate()->getGalleryImagesSource();

        $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;
        $offset = 0;

        if ($this->getDescriptionTemplate()->isGalleryImagesModeProduct()) {
            $limitGalleryImages = (int)$gallerySource['limit'];
            if ($this->getDescriptionTemplate()->isImageMainModeProduct()) {
                $offset = 1;
            }
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages($limitGalleryImages + 1);

            foreach ($galleryImagesTemp as $image) {
                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if ($this->getDescriptionTemplate()->isGalleryImagesModeAttribute()) {
            $galleryImagesTemp = $this->getMagentoProduct()->getAttributeValue($gallerySource['attribute']);
            $galleryImagesTemp = explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                $image = $this->magentoProductImageFactory->create();
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        return array_slice($galleryImages, $offset, $limitGalleryImages);
    }
}
