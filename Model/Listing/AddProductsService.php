<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing;

use M2E\Temu\Model\Product;

class AddProductsService
{
    private Product\Repository $listingProductRepository;
    private \M2E\Temu\Model\InstructionService $instructionService;
    private \M2E\Temu\Model\Listing\LogService $listingLogService;
    private \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory;
    private \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository;
    /** @var \M2E\Temu\Model\Product\CreateService */
    private Product\CreateService $createProductService;

    public function __construct(
        \M2E\Temu\Model\Product\CreateService $createProductService,
        Product\Repository $listingProductRepository,
        \M2E\Temu\Model\UnmanagedProduct\Repository $unmanagedProductRepository,
        \M2E\Temu\Model\InstructionService $instructionService,
        \M2E\Temu\Model\Listing\LogService $listingLogService,
        \M2E\Temu\Model\Magento\ProductFactory $magentoProductFactory
    ) {
        $this->listingProductRepository = $listingProductRepository;
        $this->instructionService = $instructionService;
        $this->listingLogService = $listingLogService;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->unmanagedProductRepository = $unmanagedProductRepository;
        $this->createProductService = $createProductService;
    }

    public function addProduct(
        \M2E\Temu\Model\Listing $listing,
        int $magentoProductId,
        ?int $categoryDictionaryId,
        int $initiator,
        ?\M2E\Temu\Model\UnmanagedProduct $unmanagedProduct = null
    ): ?Product {
        $listingProduct = $this->findExistProduct($listing, $magentoProductId);
        if ($listingProduct !== null) {
            return null;
        }

        $m2eMagentoProduct = $this->magentoProductFactory->createByProductId($magentoProductId);

        $listingProduct = $this->createProductService->create(
            $listing,
            $m2eMagentoProduct,
            $categoryDictionaryId,
            $unmanagedProduct,
        );

        $logMessage = (string)__('Product was Added');
        $logAction = \M2E\Temu\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_LISTING;

        if (!empty($unmanagedProduct)) {
            $logMessage = (string)__('Item was Moved');
            $logAction = \M2E\Temu\Model\Listing\Log::ACTION_MOVE_FROM_OTHER_LISTING;
        }

        // Add message for listing log
        // ---------------------------------------
        $this->listingLogService->addProduct(
            $listingProduct,
            $initiator,
            $logAction,
            null,
            $logMessage,
            \M2E\Temu\Model\Log\AbstractModel::TYPE_INFO,
        );
        // ---------------------------------------

        $this->instructionService->create(
            $listingProduct->getId(),
            \M2E\Temu\Model\Listing::INSTRUCTION_TYPE_PRODUCT_ADDED,
            \M2E\Temu\Model\Listing::INSTRUCTION_INITIATOR_ADDING_PRODUCT,
            70,
            \M2E\Core\Helper\Date::createCurrentGmt()->modify('+1 minutes')
        );

        return $listingProduct;
    }

    public function addFromUnmanaged(
        \M2E\Temu\Model\Listing $listing,
        \M2E\Temu\Model\UnmanagedProduct $unmanagedProduct,
        ?int $categoryDictionaryId,
        int $initiator
    ): ?Product {
        if (!$unmanagedProduct->hasMagentoProductId()) {
            return null;
        }

        if (!$unmanagedProduct->isListingCorrectForMove($listing)) {
            return null;
        }

        $existProduct = $this->listingProductRepository->findByChannelIds(
            [$unmanagedProduct->getChannelProductId()],
            $unmanagedProduct->getAccountId(),
        );

        if (!empty($existProduct)) {
            return null;
        }

        $existProductSku = $this->listingProductRepository->findBySkus(
            [$unmanagedProduct->getFirstSkuFromVariants()],
            $unmanagedProduct->getAccountId(),
        );

        if (!empty($existProductSku)) {
            return null;
        }

        $magentoProductId = $unmanagedProduct->getMagentoProductId();

        $listingProduct = $this->addProduct(
            $listing,
            $magentoProductId,
            $categoryDictionaryId,
            $initiator,
            $unmanagedProduct,
        );
        if ($listingProduct === null) {
            return null;
        }

        $unmanagedProduct->setMovedToListingProductId($listingProduct->getId());
        $this->unmanagedProductRepository->save($unmanagedProduct);

        $this->instructionService->create(
            $listingProduct->getId(),
            \M2E\Temu\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \M2E\Temu\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER,
            20,
        );

        return $listingProduct;
    }

    private function findExistProduct(\M2E\Temu\Model\Listing $listing, int $magentoProductId): ?Product
    {
        return $this->listingProductRepository->findByListingAndMagentoProductId($listing, $magentoProductId);
    }

    /**
     * @param \M2E\Temu\Model\Product $listingProduct
     * @param \M2E\Temu\Model\Listing $targetListing
     * @param \M2E\Temu\Model\Listing $sourceListing
     *
     * @return bool
     * @throws \Exception
     */
    public function addProductFromListing(
        \M2E\Temu\Model\Product $listingProduct,
        \M2E\Temu\Model\Listing $targetListing,
        \M2E\Temu\Model\Listing $sourceListing
    ) {
        if ($this->findExistProduct($targetListing, $listingProduct->getMagentoProductId()) !== null) {
            $this->listingLogService->addProduct(
                $listingProduct,
                \M2E\Core\Helper\Data::INITIATOR_USER,
                \M2E\Temu\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
                null,
                (string)__('The Product was not moved because it already exists in the selected Listing'),
                \M2E\Temu\Model\Log\AbstractModel::TYPE_ERROR,
            );

            return false;
        }

        $listingProduct->changeListing($targetListing);
        $this->listingProductRepository->save($listingProduct);

        $logMessage = (string)__(
            'Item was moved from Listing %previous_listing_name.',
            [
                'previous_listing_name' => $sourceListing->getTitle()
            ],
        );

        $this->listingLogService->addProduct(
            $listingProduct,
            \M2E\Core\Helper\Data::INITIATOR_USER,
            \M2E\Temu\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
            null,
            $logMessage,
            \M2E\Temu\Model\Log\AbstractModel::TYPE_INFO,
        );

        $logMessage = (string)__(
            'Product %product_title was moved to Listing %current_listing_name',
            [
                'product_title' => $listingProduct->getMagentoProduct()->getName(),
                'current_listing_name' => $targetListing->getTitle(),
            ],
        );

        $this->listingLogService->addListing(
            $sourceListing,
            \M2E\Core\Helper\Data::INITIATOR_USER,
            \M2E\Temu\Model\Listing\Log::ACTION_MOVE_TO_LISTING,
            null,
            $logMessage,
            \M2E\Temu\Model\Log\AbstractModel::TYPE_INFO,
        );

        $this->instructionService->create(
            $listingProduct->getId(),
            \M2E\Temu\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \M2E\Temu\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_LISTING,
            20
        );

        return true;
    }
}
