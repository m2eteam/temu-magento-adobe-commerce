<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Revise;

class Logger
{
    private array $logs = [];
    private \Magento\Framework\Locale\CurrencyInterface $localeCurrency;

    private float $onlinePrice;
    private int $onlineQty;
    private int $status;
    private string $onlineTitle;
    private string $onlineDescriptionHash;
    private string $onlineCategoryAttributesDataHash;
    private ?string $onlineImagesHash;
    private ?string $onlineShippingTemplateId;
    private ?int $onlinePreparationTime;
    private ?string $onlineItemTaxCode;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency
    ) {
        $this->localeCurrency = $localeCurrency;
    }

    public function saveVariantOnlineDataBeforeUpdate(\M2E\Temu\Model\Product\VariantSku\OnlineData $variantOnlineData): void
    {
        $this->onlinePrice = $variantOnlineData->getPrice();
        $this->onlineQty = $variantOnlineData->getQty();
        $this->status = $variantOnlineData->getStatus();
    }

    public function collectSuccessMessages(\M2E\Temu\Model\Product\VariantSku $variant): array
    {
        $this->generateMessageAboutChangePrice($variant);
        $this->generateMessageAboutChangeQty($variant);
        $this->generateMessageAboutChangeStatus($variant);

        return $this->logs;
    }

    private function generateMessageAboutChangePrice(\M2E\Temu\Model\Product\VariantSku $variant): void
    {
        $from = $this->onlinePrice;
        $currencyCode =  $variant->getProduct()->getCurrencyCode();
        $currency = $this->localeCurrency->getCurrency($currencyCode);

        if ($from === $variant->getOnlinePrice()) {
            return;
        }

        if ($variant->getProduct()->isSimple()) {
            $message = sprintf(
                'Product Price was revised from %s to %s',
                $currency->toCurrency($from),
                $currency->toCurrency($variant->getOnlinePrice()),
            );
        } else {
            $message = sprintf(
                'SKU %s: Price was revised from %s to %s',
                $variant->getOnlineSku(),
                $currency->toCurrency($from),
                $currency->toCurrency($variant->getOnlinePrice()),
            );
        }

        $this->logs[] = $message;
    }

    private function generateMessageAboutChangeQty(\M2E\Temu\Model\Product\VariantSku $variant): void
    {
        $from = $this->onlineQty;
        if ($from === $variant->getOnlineQty()) {
            return;
        }

        if ($variant->getProduct()->isSimple()) {
            $message = sprintf(
                'Product QTY was revised from %s to %s',
                $from,
                $variant->getOnlineQty()
            );
        } else {
            $message = sprintf(
                'SKU %s: QTY was revised from %s to %s',
                $variant->getOnlineSku(),
                $from,
                $variant->getOnlineQty()
            );
        }

        $this->logs[] = $message;
    }

    private function generateMessageAboutChangeStatus(\M2E\Temu\Model\Product\VariantSku $variant)
    {
        $from = $this->status;
        $to = $variant->getStatus();
        if ($from === $to) {
            return;
        }

        if ($variant->getProduct()->isSimple()) {
            $message = sprintf(
                'Product Status was revised from "%s" to "%s"',
                \M2E\Temu\Model\Product::getStatusTitle($from),
                \M2E\Temu\Model\Product::getStatusTitle($to)
            );
        } else {
            $message = sprintf(
                'SKU %s: Status was revised from "%s" to "%s"',
                $variant->getOnlineSku(),
                \M2E\Temu\Model\Product::getStatusTitle($from),
                \M2E\Temu\Model\Product::getStatusTitle($to)
            );
        }

        $this->logs[] = $message;
    }

    public function saveProductOnlineDataBeforeUpdate(\M2E\Temu\Model\Product $product): void
    {
        $this->onlineTitle = $product->getOnlineTitle();
        $this->onlineDescriptionHash = $product->getOnlineDescription();
        $this->onlineCategoryAttributesDataHash = $product->getOnlineCategoryData();
        $this->onlineImagesHash = $product->getOnlineImages();
        $this->onlineShippingTemplateId = $product->getOnlineShippingTemplateId();
        $this->onlinePreparationTime = $product->getOnlinePreparationTime();
        $this->onlineItemTaxCode = $product->getOnlineItemTaxCode();
    }

    public function collectProductSuccessMessages(\M2E\Temu\Model\Product $product): array
    {
        $this->generateMessageAboutChangeTitle($product);
        $this->generateMessageAboutChangeDescription($product);
        $this->generateMessageAboutChangeCategories($product);
        $this->generateMessageAboutChangeImages($product);
        $this->generateMessageAboutChangeShippingTemplateId($product);
        $this->generateMessageAboutChangeItemTaxCode($product);

        return $this->logs;
    }

    private function generateMessageAboutChangeTitle(\M2E\Temu\Model\Product $product): void
    {
        if ($this->onlineTitle !== $product->getOnlineTitle()) {
            $this->logs[] = 'Item was revised: Product Title was updated.';
        }
    }

    private function generateMessageAboutChangeDescription(\M2E\Temu\Model\Product $product): void
    {
        if ($this->onlineDescriptionHash !== $product->getOnlineDescription()) {
            $this->logs[] = 'Item was revised: Product Description was updated.';
        }
    }

    private function generateMessageAboutChangeCategories(\M2E\Temu\Model\Product $product): void
    {
        if (
            $this->onlineCategoryAttributesDataHash !== $product->getOnlineCategoryData()
        ) {
            $this->logs[] = 'Item was revised: Product Category Attributes were updated.';
        }
    }

    private function generateMessageAboutChangeImages(\M2E\Temu\Model\Product $product): void
    {
        if (
            $this->onlineImagesHash !== $product->getOnlineImages()
        ) {
            $this->logs[] = 'Item was revised: Product Images were updated.';
        }
    }

    private function generateMessageAboutChangeShippingTemplateId(\M2E\Temu\Model\Product $product): void
    {
        if (
            $this->onlineShippingTemplateId !== $product->getOnlineShippingTemplateId()
            || $this->onlinePreparationTime !== $product->getOnlinePreparationTime()
        ) {
            $this->logs[] = 'Item was revised: Shipping was updated.';
        }
    }

    private function generateMessageAboutChangeItemTaxCode(\M2E\Temu\Model\Product $product): void
    {
        if (
            $this->onlineItemTaxCode !== $product->getOnlineItemTaxCode()
        ) {
            $this->logs[] = 'Item was revised: Item Tax Code was updated.';
        }
    }
}
