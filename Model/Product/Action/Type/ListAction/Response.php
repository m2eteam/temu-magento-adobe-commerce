<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\ListAction;

use M2E\Temu\Model\Product\DataProvider;

class Response extends \M2E\Temu\Model\Product\Action\Type\AbstractResponse
{
    private \M2E\Temu\Model\Product\Repository $productRepository;

    public function __construct(
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \M2E\Temu\Model\TagFactory $tagFactory
    ) {
        parent::__construct($tagBuffer, $tagFactory);
        $this->productRepository = $productRepository;
    }

    public function process(): void
    {
        $responseData = $this->getResponseData();
        if (!empty($responseData['messages'])) {
            $this->addTags($responseData['messages']);
        }

        if ($this->isSuccess()) {
            $this->processSuccess($responseData);
        }
    }

    public function generateResultMessage(): void
    {
        $responseData = $this->getResponseData();
        if (!$this->isSuccess()) {
            if (empty($responseData['messages'])) {
                $this->getLogBuffer()->addFail('Product failed to be listed.');

                return;
            }

            $existTexts = [];
            foreach ($responseData['messages'] as $messageData) {
                $text = $messageData['text'];

                if (isset($existTexts[$text])) {
                    continue;
                }

                $existTexts[$text] = true;

                $this->getLogBuffer()->addFail(
                    sprintf(
                        'Product failed to be listed. Reason: %s',
                        $text
                    )
                );
            }

            return;
        }

        foreach ($responseData['messages'] ?? [] as $messageData) {
            if ($messageData['type'] === \M2E\Core\Model\Response\Message::TYPE_WARNING) {
                $this->getLogBuffer()->addWarning($messageData['text']);
            }
        }

        $message = 'Product was Listed';

        $this->getLogBuffer()->addSuccess($message);
    }

    protected function processSuccess(array $responseData): void
    {
        $product = $this->getProduct();

        $metadata = $this->getRequestMetaData();

        $this->processVariants($responseData['product']['skus']);

        $product->setStatusListed($responseData['product']['id'], $this->getStatusChanger())
                ->setOnlineDescription($metadata[DataProvider\DescriptionProvider::NICK]['online_description'])
                ->setOnlineTitle($metadata[DataProvider\TitleProvider::NICK]['online_title'])
                ->setOnlineImages($metadata[DataProvider\ImagesProvider::NICK]['images'])
                ->setOnlineCategoryId($metadata[DataProvider\CategoryProvider::NICK])
                ->setOnlineCategoryData($metadata[DataProvider\ProductAttributesProvider::NICK])
                ->setOnlineBulletPoints($metadata[DataProvider\BulletPointsProvider::NICK])
                ->setOnlineItemTaxCode($metadata[DataProvider\ItemTaxCodeProvider::NICK] ?? null)
                ->removeBlockingByError()
                ->recalculateOnlineDataByVariants();

        if (isset($metadata[DataProvider\ShippingProvider::NICK]['template_id'])) {
            $product->setOnlineShippingTemplateId($metadata[DataProvider\ShippingProvider::NICK]['template_id']);
        }

        if (isset($metadata[DataProvider\ShippingProvider::NICK]['limit_day'])) {
            $product->setOnlinePreparationTime((int)$metadata[DataProvider\ShippingProvider::NICK]['limit_day']);
        }

        $this->productRepository->save($product);
    }

    private function isSuccess(): bool
    {
        $responseData = $this->getResponseData();

        return $responseData['status'] === true;
    }

    private function processVariants(array $responseSkus): void
    {
        $responseVariantSku = [];
        foreach ($responseSkus as $sku) {
            $responseVariantSku[$sku['sku']] = [
                'sku' => $sku['sku'],
                'sku_id' => $sku['id'],
            ];
        }

        foreach ($this->getProduct()->getVariants() as $variant) {
            if ($this->getVariantSettings()->isSkipAction($variant->getId())) {
                continue;
            }

            $variantSku = $variant->getSku();

            if (isset($responseVariantSku[$variantSku])) {
                $qty = $this->getOnlineQtyForVariant($variantSku);

                $variant
                    ->setSkuId((string)$responseVariantSku[$variantSku]['sku_id'])
                    ->setOnlineSku($this->getOnlineSkuForVariant($variantSku))
                    ->setOnlineQty($qty)
                    ->setOnlinePrice($this->getOnlinePriceForVariant($variantSku))
                    ->setOnlineImage($this->getOnlineImageForVariant($variantSku))
                    ->setOnlineReferenceLink($this->getOnlineReferenceLinkForVariant($variantSku));

                if ($qty > 0) {
                    $variant->changeStatusToListed();
                } else {
                    $variant->changeStatusToInactive();
                }
            }

            $this->productRepository->saveVariantSku($variant);
        }
    }

    private function getOnlineSkuForVariant(string $sku): string
    {
        $metadata = $this->getRequestMetaData();

        return $metadata[DataProvider\VariantsProvider::NICK][$sku]['online_sku'];
    }

    private function getOnlinePriceForVariant(string $sku): float
    {
        $metadata = $this->getRequestMetaData();

        return $metadata[DataProvider\VariantsProvider::NICK][$sku]['online_price'] ?? 0;
    }

    private function getOnlineQtyForVariant(string $sku): int
    {
        $metadata = $this->getRequestMetaData();

        return $metadata[DataProvider\VariantsProvider::NICK][$sku]['online_qty'] ?? 0;
    }

    private function getOnlineImageForVariant(string $sku): string
    {
        $metadata = $this->getRequestMetaData();

        return $metadata[DataProvider\VariantsProvider::NICK][$sku]['images'] ?? '';
    }

    private function getOnlineReferenceLinkForVariant(string $sku): ?string
    {
        $metadata = $this->getRequestMetaData();

        return $metadata[DataProvider\VariantsProvider::NICK][$sku]['online_reference_link'] ?? null;
    }
}
