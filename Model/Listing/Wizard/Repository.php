<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing\Wizard;

use M2E\Temu\Model\ResourceModel\Listing\Wizard as WizardResource;
use M2E\Temu\Model\ResourceModel\Listing\Wizard\Product as WizardProductResource;

class Repository
{
    private WizardResource $wizardResource;
    private \M2E\Temu\Model\ResourceModel\Listing\Wizard\Step $stepResource;
    private \M2E\Temu\Model\ResourceModel\Listing\Wizard\Step\CollectionFactory $stepCollectionFactory;
    private \M2E\Temu\Model\Listing\WizardFactory $wizardFactory;
    private \M2E\Temu\Model\ResourceModel\Listing\Wizard\CollectionFactory $wizardCollectionFactory;
    private WizardProductResource $wizardProductResource;
    private \M2E\Temu\Model\Listing\Wizard\ProductCollectionFactory $productCollectionFactory;
    private \M2E\Temu\Model\ResourceModel\Listing\Wizard\Product\CollectionFactory $wizardProductCollectionFactory;

    public function __construct(
        \M2E\Temu\Model\Listing\WizardFactory $wizardFactory,
        WizardResource $wizardResource,
        \M2E\Temu\Model\ResourceModel\Listing\Wizard\CollectionFactory $wizardCollectionFactory,
        \M2E\Temu\Model\ResourceModel\Listing\Wizard\Step $stepResource,
        \M2E\Temu\Model\ResourceModel\Listing\Wizard\Step\CollectionFactory $stepCollectionFactory,
        WizardProductResource $wizardProductResource,
        \M2E\Temu\Model\ResourceModel\Listing\Wizard\Product\CollectionFactory $wizardProductCollectionFactory,
        \M2E\Temu\Model\Listing\Wizard\ProductCollectionFactory $productCollectionFactory
    ) {
        $this->wizardResource = $wizardResource;
        $this->stepResource = $stepResource;
        $this->stepCollectionFactory = $stepCollectionFactory;
        $this->wizardFactory = $wizardFactory;
        $this->wizardCollectionFactory = $wizardCollectionFactory;
        $this->wizardProductResource = $wizardProductResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->wizardProductCollectionFactory = $wizardProductCollectionFactory;
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return void
     */
    public function create(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        $this->wizardResource->save($wizard);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard\Step[] $steps
     *
     * @return void
     */
    public function createSteps(array $steps): void
    {
        foreach ($steps as $step) {
            $this->stepResource->save($step);
        }
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return void
     */
    public function save(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        $this->wizardResource->save($wizard);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard\Step $step
     *
     * @return void
     */
    public function saveStep(Step $step): void
    {
        $this->stepResource->save($step);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard\Product $product
     *
     * @return void
     */
    public function saveProduct(Product $product): void
    {
        $this->wizardProductResource->save($product);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return void
     */
    public function remove(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        foreach ($wizard->getSteps() as $step) {
            $this->stepResource->delete($step);
        }
        $this->removeAllProducts($wizard);

        $this->wizardResource->delete($wizard);
    }

    // ----------------------------------------

    /**
     * @param int $id
     *
     * @return \M2E\Temu\Model\Listing\Wizard
     */
    public function get(int $id): \M2E\Temu\Model\Listing\Wizard
    {
        $wizard = $this->find($id);
        if ($wizard === null) {
            throw new \M2E\Temu\Model\Listing\Wizard\Exception\NotFoundException('Wizard not found.');
        }

        return $wizard;
    }

    /**
     * @param int $id
     *
     * @return \M2E\Temu\Model\Listing\Wizard|null
     */
    public function find(int $id): ?\M2E\Temu\Model\Listing\Wizard
    {
        $wizard = $this->wizardFactory->create();
        $this->wizardResource->load($wizard, $id);

        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);

        return $wizard;
    }

    /**
     * @param \M2E\Temu\Model\Listing $listing
     * @param string $type
     *
     * @return \M2E\Temu\Model\Listing\Wizard|null
     */
    public function findNotCompletedByListingAndType(
        \M2E\Temu\Model\Listing $listing,
        string $type
    ): ?\M2E\Temu\Model\Listing\Wizard {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard::COLUMN_LISTING_ID, ['eq' => $listing->getId()])
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard::COLUMN_IS_COMPLETED, ['eq' => 0])
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard::COLUMN_TYPE, ['eq' => $type]);

        $wizard = $collection->getFirstItem();
        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);
        $wizard->initListing($listing);

        return $wizard;
    }

    /**
     * @param string $type
     *
     * @return \M2E\Temu\Model\Listing\Wizard|null
     */
    public function findNotCompletedWizardByType(string $type): ?\M2E\Temu\Model\Listing\Wizard
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard::COLUMN_IS_COMPLETED, ['eq' => 0])
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard::COLUMN_TYPE, ['eq' => $type]);

        $wizard = $collection->getFirstItem();
        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);

        return $wizard;
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Step[]
     */
    public function findSteps(\M2E\Temu\Model\Listing\Wizard $wizard): array
    {
        $stepCollection = $this->stepCollectionFactory->create();
        $stepCollection->addFieldToFilter(
            \M2E\Temu\Model\ResourceModel\Listing\Wizard\Step::COLUMN_WIZARD_ID,
            $wizard->getId(),
        );

        return array_values($stepCollection->getItems());
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return void
     */
    private function loadSteps(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        $steps = $this->findSteps($wizard);
        $wizard->initSteps($steps);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function findAllProducts(\M2E\Temu\Model\Listing\Wizard $wizard): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter(
            \M2E\Temu\Model\ResourceModel\Listing\Wizard\Product::COLUMN_WIZARD_ID,
            $wizard->getId(),
        );

        $result = [];
        foreach ($productCollection->getItems() as $product) {
            $product->initWizard($wizard);

            $result[] = $product;
        }

        return $result;
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function findNotProcessed(\M2E\Temu\Model\Listing\Wizard $wizard): array
    {
        $collection = $this->wizardProductCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId())
            ->addFieldToFilter(WizardProductResource::COLUMN_IS_PROCESSED, 0);

        $result = [];
        foreach ($collection->getItems() as $product) {
            $product->initWizard($wizard);

            $result[] = $product;
        }

        return $result;
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return int
     */
    public function getProcessedProductsCount(\M2E\Temu\Model\Listing\Wizard $wizard): int
    {
        $connection = $this->wizardProductResource->getConnection();
        $tableName = $this->wizardProductResource->getMainTable();

        $select = $connection->select()
                             ->from($tableName, ['COUNT(*)'])
                             ->where(WizardProductResource::COLUMN_WIZARD_ID . ' = ?', $wizard->getId())
                             ->where(WizardProductResource::COLUMN_IS_PROCESSED . ' = ?', 1);

        return (int)$connection->fetchOne($select);
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return void
     */
    public function removeAllProducts(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        $this->wizardProductResource->getConnection()->delete(
            $this->wizardProductResource->getMainTable(),
            [WizardProductResource::COLUMN_WIZARD_ID . ' = ?' => $wizard->getId()],
        );
    }

    /**
     * @param int $id
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product|null
     */
    public function findProductById(
        int $id,
        \M2E\Temu\Model\Listing\Wizard $wizard
    ): ?\M2E\Temu\Model\Listing\Wizard\Product {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(
                \M2E\Temu\Model\ResourceModel\Listing\Wizard\Product::COLUMN_WIZARD_ID,
                $wizard->getId()
            )
            ->addFieldToFilter(
                \M2E\Temu\Model\ResourceModel\Listing\Wizard\Product::COLUMN_ID,
                ['eq' => $id]
            );

        $product = $productCollection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        $product->initWizard($wizard);

        return $product;
    }

    /**
     * @param int $id
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product|null
     */
    public function findProductByMagentoId(
        int $id,
        \M2E\Temu\Model\Listing\Wizard $wizard
    ): ?\M2E\Temu\Model\Listing\Wizard\Product {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard\Product::COLUMN_WIZARD_ID, $wizard->getId())
            ->addFieldToFilter(\M2E\Temu\Model\ResourceModel\Listing\Wizard\Product::COLUMN_MAGENTO_PRODUCT_ID, ['eq' => $id]);

        $product = $productCollection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        $product->initWizard($wizard);

        return $product;
    }

    /**
     * @param \DateTime $borderDate
     *
     * @return \M2E\Temu\Model\Listing\Wizard[]
     */
    public function findOldCompleted(\DateTime $borderDate): array
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_IS_COMPLETED, ['eq' => 1])
            ->addFieldToFilter(WizardResource::COLUMN_PROCESS_END_DATE, ['lt' => $borderDate->format('Y-m-d H:i:s')]);

        return array_values($collection->getItems());
    }

    /**
     * @param \M2E\Temu\Model\Listing $listing
     *
     * @return \M2E\Temu\Model\Listing\Wizard[]
     */
    public function findWizardsByListing(\M2E\Temu\Model\Listing $listing): array
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_LISTING_ID, ['eq' => $listing->getId()]);

        return array_values($collection->getItems());
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     * @param int[] $wizardProductsIds
     *
     * @return void
     */
    public function markProductsAsCompleted(
        \M2E\Temu\Model\Listing\Wizard $wizard,
        array $wizardProductsIds
    ): void {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_IS_PROCESSED => 1,
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId()),
                    sprintf('%s IN (%s)', WizardProductResource::COLUMN_ID, implode(',', $wizardProductsIds)),
                ],
            );
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     * @param int $categoryDictionaryId
     *
     * @return void
     */
    public function setCategoryDictionaryIdForAllProducts(
        \M2E\Temu\Model\Listing\Wizard $wizard,
        int $categoryDictionaryId
    ): void {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_CATEGORY_ID => $categoryDictionaryId,
                ],
                [
                    sprintf('%s = ?', WizardProductResource::COLUMN_WIZARD_ID) => $wizard->getId(),
                ],
            );
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     * @param int[] $productsIds
     *
     * @return void
     */
    public function resetCategoryIdByProductId(
        \M2E\Temu\Model\Listing\Wizard $wizard,
        array $productsIds
    ): void {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_CATEGORY_ID => null,
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId()),
                    sprintf('%s in (?)', WizardProductResource::COLUMN_ID) => $productsIds,
                ],
            );
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard\Product[] $wizardProducts
     *
     * @return void
     */
    public function addOrUpdateProducts(array $wizardProducts): void
    {
        if (empty($wizardProducts)) {
            return;
        }

        $tableName = $this->wizardProductResource->getMainTable();
        $connection = $this->wizardProductResource->getConnection();

        foreach (array_chunk($wizardProducts, 500) as $productsChunk) {
            $preparedData = [];
            /** @var \M2E\Temu\Model\Listing\Wizard\Product $product */
            foreach ($productsChunk as $product) {
                $preparedData[] = [
                    'wizard_id' => $product->getWizardId(),
                    'unmanaged_product_id' => $product->getUnmanagedProductId(),
                    'magento_product_id' => $product->getMagentoProductId(),
                    'category_id' => $product->getCategoryDictionaryId(),
                    'is_processed' => (int)$product->isProcessed(),
                ];
            }

            $connection->insertOnDuplicate($tableName, $preparedData, ['category_id', 'is_processed']);
        }
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     *
     * @return int
     */
    public function getProductCount(\M2E\Temu\Model\Listing\Wizard $wizard): int
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(
                WizardProductResource::COLUMN_WIZARD_ID,
                ['eq' => $wizard->getId()],
            );

        return (int)$productCollection->getSize();
    }

    /**
     * @param \M2E\Temu\Model\Listing\Wizard $wizard
     * @param int $productLimit
     *
     * @return \M2E\Temu\Model\Listing\Wizard\Product[]
     */
    public function findProductsForValidateCategoryAttributes(\M2E\Temu\Model\Listing\Wizard $wizard, int $productLimit): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(
                WizardProductResource::COLUMN_WIZARD_ID,
                ['eq' => $wizard->getId()],
            )
            ->addFieldToFilter(
                WizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES,
                ['null' => true]
            )
            ->addFieldToFilter(
                WizardProductResource::COLUMN_CATEGORY_ID,
                ['notnull' => true]
            )
            ->setPageSize($productLimit);

        $result = [];
        foreach ($productCollection->getItems() as $product) {
            $product->initWizard($wizard);

            $result[] = $product;
        }

        return $result;
    }

    public function resetCategoryAttributesValidationData(\M2E\Temu\Model\Listing\Wizard $wizard): void
    {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES => null,
                    WizardProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS => null,
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId()),
                ],
            );
    }

    public function resetCategoryAttributesValidationDataByCategoryId(int $categoryId): void
    {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES => null,
                    WizardProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS => null,
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_CATEGORY_ID, $categoryId),
                ],
            );
    }
}
