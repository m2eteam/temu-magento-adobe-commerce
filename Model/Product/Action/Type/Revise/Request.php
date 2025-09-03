<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type\Revise;

class Request extends \M2E\Temu\Model\Product\Action\AbstractRequest
{
    use \M2E\Temu\Model\Product\Action\RequestTrait;

    private const DETAIL_TYPES = [
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_IMAGES,
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_TITLE,
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_DESCRIPTION,
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_CATEGORIES,
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_SHIPPING,
        \M2E\Temu\Model\Product\Action\Configurator::DATA_TYPE_PRICE,
    ];

    private array $metadata = [];

    private \M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository;

    public function __construct(\M2E\Temu\Model\Product\VariantSku\Deleted\Repository $deletedVariantSkuRepository)
    {
        $this->deletedVariantSkuRepository = $deletedVariantSkuRepository;
    }

    public function getActionData(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings,
        array $params
    ): array {
        if ($this->isNeedUpdateDetail($product, $actionConfigurator)) {
            return $this->createRequestForDetailRevise($product, $actionConfigurator);
        }

        return $this->createRequestQtyAndPriceUpdate($product);
    }

    protected function getActionMetadata(): array
    {
        return $this->metadata;
    }

    private function isNeedUpdateDetail(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator
    ): bool {
        if ($this->deletedVariantSkuRepository->hasByProductId($product->getId())) {
            return false;
        }

        if (empty($product->getOnlineCategoryId())) {
            return false;
        }

        foreach ($actionConfigurator->getAllowedDataTypes() as $type) {
            if (in_array($type, self::DETAIL_TYPES)) {
                return true;
            }
        }

        return false;
    }

    private function createRequestQtyAndPriceUpdate(\M2E\Temu\Model\Product $product): array
    {
        $dataProvider = $product->getDataProvider();
        $variantSkus = $dataProvider->getVariantSkusForRevise()->getValue();
        $request['id'] = $product->getChannelProductId();

        $request['skus'] = array_map(
            static function (\M2E\Temu\Model\Product\DataProvider\Variants\Item $item) {
                return [
                    'id' => $item->getSkuId(),
                    'price' => $item->getPrice(),
                    'currency_code' => $item->getCurrency(),
                    'qty' => $item->getQty(),
                    'is_deleted_variation' => $item->isDeletedVariation(),
                ];
            },
            $variantSkus->items
        );

        $this->metadata = $dataProvider->getMetaData();

        $this->processDataProviderLogs($dataProvider);

        return $request;
    }

    private function createRequestForDetailRevise(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator
    ): array {
        $dataProvider = $product->getDataProvider();
        $variantSkus = $dataProvider->getVariantSkusForReviseDetails()->getValue();

        $request['id'] = $product->getChannelProductId();
        $request['category_id'] = $dataProvider->getCategoryData()->getValue();

        $request['skus'] = array_map(
            static function (\M2E\Temu\Model\Product\DataProvider\Variants\Item $item) {
                return [
                    'id' => $item->getSkuId(),
                    'price' => $item->getPrice(),
                    'currency_code' => $item->getCurrency(),
                    'qty' => $item->getQty(),
                    'images' => array_map(
                        static fn($image) => $image->url,
                        $item->getImages()
                    ),
                    'package_weight' => $item->getPackageWeight(),
                    'package_dimensions' => $item->getPackageDimensions(),
                    'reference_link' => $item->getReferenceLink()
                ];
            },
            $variantSkus->items
        );

        if ($actionConfigurator->isTitleAllowed()) {
            $request['title'] = $dataProvider->getTitle()->getValue();
        }

        if ($actionConfigurator->isDescriptionAllowed()) {
            $request['description'] = $dataProvider->getDescription()->getValue()->description;
            $request['bullet_points'] = $dataProvider->getBulletsPoints()->getValue()->bulletPoints;
        }

        if ($actionConfigurator->isImagesAllowed()) {
            $request['images'] = array_map(
                static function (\M2E\Temu\Model\Product\DataProvider\Images\Image $image) {
                    return $image->url;
                },
                $dataProvider->getImages()->getValue()->set
            );
        }

        if ($actionConfigurator->isCategoriesAllowed()) {
            $attributes = $dataProvider->getProductAttributesData()->getValue();
            $request['attributes'] = array_map(
                static function (\M2E\Temu\Model\Product\DataProvider\Attributes\Item $attribute) {
                    return [
                        'pid' => $attribute->getPid(),
                        'ref_pid' => $attribute->getRefPid(),
                        'template_pid' => $attribute->getTemplatePid(),
                        'value' => $attribute->getValue(),
                        'value_id' => $attribute->getValueId(),
                        'name' => $attribute->getName(),
                    ];
                },
                $attributes->items
            );
        }

        if ($actionConfigurator->isShippingAllowed()) {
            $request['shipping'] = [
                'template_id' => $dataProvider->getShipping()->getValue()->shippingTemplateId,
                'limit_day' => $dataProvider->getShipping()->getValue()->preparationTime,
            ];
        }

        if ($actionConfigurator->isPriceAllowed()) {
            $request['item_tax_code'] = $dataProvider->getItemTaxCode()->getValue();
        }

        $metadata = $dataProvider->getMetaData();

        [$request, $metadata] = $this->unsetCategoryDataIfCategoryChanged($product, $request, $metadata);

        $this->metadata = $metadata;

        $this->processDataProviderLogs($dataProvider);

        return $request;
    }

    private function unsetCategoryDataIfCategoryChanged(
        \M2E\Temu\Model\Product $product,
        array $request,
        array $metadata
    ): array {
        $currentCategoryId = (int)$request['category_id'];
        if ($currentCategoryId === $product->getOnlineCategoryId()) {
            return [$request, $metadata];
        }

        $request['category_id'] = $product->getOnlineCategoryId();
        unset($request['attributes']);
        unset($metadata[\M2E\Temu\Model\Product\DataProvider\ProductAttributesProvider::NICK]);
        unset($metadata[\M2E\Temu\Model\Product\DataProvider\CategoryProvider::NICK]);

        return [$request, $metadata];
    }
}
