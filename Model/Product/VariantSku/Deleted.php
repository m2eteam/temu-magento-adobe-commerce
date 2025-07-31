<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku;

use M2E\Temu\Model\ResourceModel\Product\VariantSku\Deleted as VariantSkuDeletedResource;

class Deleted extends \M2E\Temu\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(VariantSkuDeletedResource::class);
    }

    public function initFromVariant(\M2E\Temu\Model\Product\VariantSku $variantSku): self
    {
        $this->setData(VariantSkuDeletedResource::COLUMN_PRODUCT_ID, $variantSku->getProductId());
        $this->setData(VariantSkuDeletedResource::COLUMN_REMOVED_MAGENTO_PRODUCT_ID, $variantSku->getMagentoProductId());
        $this->setData(VariantSkuDeletedResource::COLUMN_SKU_ID, $variantSku->getSkuId());
        $this->setData(VariantSkuDeletedResource::COLUMN_ONLINE_SKU, $variantSku->getOnlineSku());
        $this->setData(VariantSkuDeletedResource::COLUMN_ONLINE_PRICE, $variantSku->getOnlinePrice());
        $this->setData(
            VariantSkuDeletedResource::COLUMN_VARIATION_DATA,
            $variantSku->getData(\M2E\Temu\Model\ResourceModel\Product\VariantSku::COLUMN_VARIATION_DATA)
        );

        return $this;
    }

    public function initFromUnmanagedVariant(\M2E\Temu\Model\UnmanagedProduct\VariantSku $unmanagedVariant): self
    {
        $this->setData(VariantSkuDeletedResource::COLUMN_SKU_ID, $unmanagedVariant->getSkuId());
        $this->setData(VariantSkuDeletedResource::COLUMN_ONLINE_SKU, $unmanagedVariant->getSku());
        $this->setData(VariantSkuDeletedResource::COLUMN_ONLINE_PRICE, $unmanagedVariant->getPrice());

        $variationData = new \M2E\Temu\Model\Product\VariantSku\Dto\VariationData();
        foreach ($unmanagedVariant->getSpecification()->getSpecific() as $specific) {
            $variationData->add(new \M2E\Temu\Model\Product\VariantSku\Dto\VariationDataItem(
                '',
                $specific->getTitle(),
                $specific->getValueTitle()
            ));
        }

        $this->setData(
            VariantSkuDeletedResource::COLUMN_VARIATION_DATA,
            $variationData->exportToJson()
        );

        return $this;
    }

    public function setProductId(int $productId): self
    {
        $this->setData(VariantSkuDeletedResource::COLUMN_PRODUCT_ID, $productId);

        return $this;
    }

    public function getSkuId()
    {
        return $this->getData(VariantSkuDeletedResource::COLUMN_SKU_ID);
    }

    public function getOnlinePrice(): float
    {
        return (float)$this->getData(VariantSkuDeletedResource::COLUMN_ONLINE_PRICE);
    }
}
