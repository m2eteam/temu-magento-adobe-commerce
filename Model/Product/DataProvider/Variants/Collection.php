<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider\Variants;

class Collection
{
    /** @var Item[] */
    public array $items = [];

    public function addItem(Item $item)
    {
        $this->items[] = $item;
    }

    public function collectOnlineDataForRevise(): array
    {
        $onlineData = [];
        foreach ($this->items as $item) {
            $onlineData[$item->getSkuId()] = [
                'sku_id' => $item->getSkuId(),
                'online_price' => $item->getPrice(),
                'online_qty' => $item->getQty(),
            ];
        }

        return $onlineData;
    }

    public function collectOnlineDataForReviseDetails(): array
    {
        $onlineData = [];
        foreach ($this->items as $item) {
            $onlineData[$item->getSkuId()] = [
                'sku_id' => $item->getSkuId(),
                'online_price' => $item->getPrice(),
                'online_qty' => $item->getQty(),
                'images' => $this->generateImagesHash($item->getImages()),
                'package_weight' => $item->getPackageWeight(),
                'package_dimensions' => $item->getPackageDimensions(),
                'online_reference_link' => $item->getReferenceLink()
            ];
        }

        return $onlineData;
    }

    public function collectOnlineDataForList(): array
    {
        $onlineData = [];
        foreach ($this->items as $item) {
            $onlineData[$item->getSku()] = [
                'online_sku' => $item->getSku(),
                'online_price' => $item->getPrice(),
                'online_qty' => $item->getQty(),
                'images' => $this->generateImagesHash($item->getImages()),
                'variation_attributes' => $item->getVariationAttributes(),
                'package_weight' => $item->getPackageWeight(),
                'package_dimensions' => $item->getPackageDimensions(),
                'online_reference_link' => $item->getReferenceLink()
            ];
        }

        return $onlineData;
    }

    /**
     * @param \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Image[] $set
     */
    private function generateImagesHash(array $set): ?string
    {
        if (empty($set)) {
            return null;
        }

        $flatImages = [];
        foreach ($set as $image) {
            $flatImages[] = $image->url;
        }

        sort($flatImages);

        return \M2E\Core\Helper\Data::md5String(json_encode($flatImages));
    }
}
