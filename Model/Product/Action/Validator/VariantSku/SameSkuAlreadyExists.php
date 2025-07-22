<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator\VariantSku;

use M2E\Temu\Model\Product\Action\Validator\ValidatorMessage;

class SameSkuAlreadyExists implements ValidatorInterface
{
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository;
    private \M2E\Temu\Model\Product\Repository $productRepository;

    public function __construct(
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedRepository,
        \M2E\Temu\Model\Product\Repository $productRepository
    ) {
        $this->unmanagedRepository = $unmanagedRepository;
        $this->productRepository = $productRepository;
    }

    public function validate(\M2E\Temu\Model\Product\VariantSku $variant): ?ValidatorMessage
    {
        $temuProductSku = $variant->getOnlineSku();

        if (empty($temuProductSku)) {
            $temuProductSku = $variant->getSku();
        }

        $existUnmanagedVariant = $this->unmanagedRepository->findVariantBySkuAndAccountId(
            $temuProductSku,
            $variant->getProduct()->getAccount()->getId(),
        );

        if ($existUnmanagedVariant !== null) {
            return new ValidatorMessage(
                (string)__(
                    'Product with the same SKU already exists in Unmanaged Items.
                 Once the Item is mapped to a Magento Product, it can be moved to an %extension_title Listing.',
                    [
                        'extension_title' => \M2E\Temu\Helper\Module::getExtensionTitle(),
                    ]
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_DUPLICATE_SKU_UNMANAGED
            );
        }

        $existListVariant = $this->productRepository->findVariantBySkuAndAccount(
            $temuProductSku,
            $variant->getProduct()->getAccount()->getId(),
        );

        if ($existListVariant !== null) {
            return new ValidatorMessage(
                (string)__(
                    'Product with the same SKU already exists in your %listing_title Listing.',
                    [
                        'listing_title' => $existListVariant->getListing()->getTitle(),
                    ]
                ),
                \M2E\Temu\Model\Tag\ValidatorIssues::ERROR_DUPLICATE_SKU_LISTING
            );
        }

        return null;
    }
}
