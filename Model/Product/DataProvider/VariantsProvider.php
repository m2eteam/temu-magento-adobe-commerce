<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider;

class VariantsProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'Variants';

    private array $onlineDataForSku = [];

    private \M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository;

    public function __construct(\M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository)
    {
        $this->deletedVariantSkuRepository = $deletedVariantSkuRepository;
    }

    public function getVariantSkusForRevise(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): \M2E\Temu\Model\Product\DataProvider\Variants\Collection {
        $variants = $product->getVariants();
        $skuItems = new \M2E\Temu\Model\Product\DataProvider\Variants\Collection();

        $currency = $product->getCurrencyCode();
        foreach ($variants as $variant) {
            if ($variantSettings->isSkipAction($variant->getId())) {
                continue;
            }

            $item = new Variants\Item();
            $item->setSkuId($variant->getSkuId());
            $item->setPrice($this->getVariantPrice($variant));
            $item->setCurrency($currency);
            $item->setQty($this->getVariantQty($variant, $variantSettings));
            $item->setIsDeletedVariation(false);

            $skuItems->addItem($item);
        }

        $deletedVariants = $this->deletedVariantSkuRepository
            ->getByProductId($product->getId());

        foreach ($deletedVariants as $deletedVariant) {
            $item = new Variants\Item();
            $item->setSkuId($deletedVariant->getSkuId());
            $item->setPrice($deletedVariant->getOnlinePrice());
            $item->setCurrency($currency);
            $item->setQty(0);
            $item->setIsDeletedVariation(true);

            $skuItems->addItem($item);
        }

        $this->onlineDataForSku = $skuItems->collectOnlineDataForRevise();

        return $skuItems;
    }

    public function getVariantSkusForReviseDetails(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): \M2E\Temu\Model\Product\DataProvider\Variants\Collection {
        $variants = $product->getVariants();
        $skuItems = new \M2E\Temu\Model\Product\DataProvider\Variants\Collection();

        $currency = $product->getCurrencyCode();
        foreach ($variants as $variant) {
            if ($variantSettings->isSkipAction($variant->getId())) {
                continue;
            }

            $item = new Variants\Item();
            $item->setSkuId($variant->getSkuId());
            $item->setPrice($this->getVariantPrice($variant));
            $item->setCurrency($currency);
            $item->setQty($this->getVariantQty($variant, $variantSettings));
            $item->setImages($variant->getDataProvider()->getImages()->getValue()->set);
            try {
                $packageWeight = $variant->getDataProvider()->getPackage()->getValue()->packageWeight;
                $item->setPackageWeight($packageWeight);
                $packageDimension = $variant->getDataProvider()->getPackage()->getValue()->packageDimensions;
                $item->setPackageDimensions($packageDimension);
            } catch (\M2E\Temu\Model\Product\PackageDimension\PackageDimensionException $exception) {
            }
            $item->setReferenceLink($variant->getDataProvider()->getReferenceLink()->getValue());

            $skuItems->addItem($item);
        }

        $this->onlineDataForSku = $skuItems->collectOnlineDataForReviseDetails();

        return $skuItems;
    }

    public function getVariantSkusForList(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): \M2E\Temu\Model\Product\DataProvider\Variants\Collection {
        $variants = $product->getVariants();
        $skuItems = new \M2E\Temu\Model\Product\DataProvider\Variants\Collection();

        foreach ($variants as $variant) {
            if ($variantSettings->isSkipAction($variant->getId())) {
                continue;
            }

            $item = new Variants\Item();
            $item->setSku($variant->getSku());
            $item->setQty($this->getVariantQty($variant, $variantSettings));
            $item->setPrice($this->getVariantPrice($variant));

            $item->setImages($variant->getDataProvider()->getImages()->getValue()->set);
            $item->setIdentifier($variant->getDataProvider()->getIdentifier()->getValue());
            $item->setReferenceLink($variant->getDataProvider()->getReferenceLink()->getValue());
            $item->setVariationAttributes($this->getVariationSalesAttributes($variant));
            try {
                $packageWeight = $variant->getDataProvider()->getPackage()->getValue()->packageWeight;
                $item->setPackageWeight($packageWeight);
                $packageDimension = $variant->getDataProvider()->getPackage()->getValue()->packageDimensions;
                $item->setPackageDimensions($packageDimension);
            } catch (\M2E\Temu\Model\Product\PackageDimension\PackageDimensionException $exception) {
            }

            $skuItems->addItem($item);
        }

        $this->onlineDataForSku = $skuItems->collectOnlineDataForList();

        return $skuItems;
    }

    public function getMetaData(): array
    {
        return [self::NICK => $this->onlineDataForSku];
    }

    // ----------------------------------------

    public function getVariantSkuIds(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): array {
        $variants = $product->getVariants();

        $variantSkuIds = [];
        foreach ($variants as $variant) {
            if ($variantSettings->isSkipAction($variant->getId())) {
                continue;
            }

            $variantSkuIds[] = [
                'id' => $variant->getSkuId()
            ];
        }

        return $variantSkuIds;
    }

    // ----------------------------------------

    private function getVariantQty(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): int {
        if ($variantSettings->isStopAction($variant->getId())) {
            return 0;
        }

        $qty = $variant->getDataProvider()->getQty()->getValue();
        if ($variant->getDataProvider()->getQty()->getMessages()) {
            $this->addWarningMessages(
                $variant->getDataProvider()->getQty()->getMessages()
            );
        }

        return $qty;
    }

    private function getVariantPrice(\M2E\Temu\Model\Product\VariantSku $variant): float
    {
        $price = $variant->getDataProvider()->getPrice()->getValue()->price;
        if ($variant->getDataProvider()->getPrice()->getMessages()) {
            $this->addWarningMessages(
                $variant->getDataProvider()->getQty()->getMessages()
            );
        }

        return $price;
    }

    private function getVariationSalesAttributes(\M2E\Temu\Model\Product\VariantSku $variant): array
    {
        $variationAttributes = $variant->getDataProvider()->getSalesAttributesData()->getValue();
        if ($variant->getDataProvider()->getSalesAttributesData()->getMessages()) {
            $this->addWarningMessages(
                $variant->getDataProvider()->getSalesAttributesData()->getMessages()
            );
        }

        return $variationAttributes;
    }

    private function addWarningMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addWarningMessage(
                $message
            );
        }
    }
}
