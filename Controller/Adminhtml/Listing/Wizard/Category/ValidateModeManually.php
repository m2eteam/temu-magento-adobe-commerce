<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Listing\Wizard\Category;

use M2E\Temu\Controller\Adminhtml\AbstractListing;

class ValidateModeManually extends AbstractListing
{
    use \M2E\Temu\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private const CATEGORY_NOT_SET = 'category_not_set';
    private const REQUIRED_ATTRIBUTES_NOT_SET = 'required_attributes_not_set';
    private const CATEGORY_AND_ATTRIBUTES_VALID = 'category_and_attributes_valid';

    private array $cachedCategoryResult = [];
    private \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository;
    private \M2E\Temu\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeCollectionFactory;
    private \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\Repository $categoryDictionaryRepository,
        \M2E\Temu\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeCollectionFactory,
        \M2E\Temu\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory
    ) {
        parent::__construct();

        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->wizardManagerFactory = $wizardManagerFactory;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($id);
        $wizardProducts = $manager->getNotProcessedProducts();

        $categoryNotSet = [];
        $requiredAttributesNotSet = [];
        $succeedProducersIds = [];

        foreach ($wizardProducts as $product) {
            $validateResult = $this->validateCategory($product->getCategoryDictionaryId());
            switch ($validateResult) {
                case self::CATEGORY_AND_ATTRIBUTES_VALID:
                    $succeedProducersIds[] = $product->getMagentoProductId();
                    break;
                case self::REQUIRED_ATTRIBUTES_NOT_SET:
                    $requiredAttributesNotSet[] = $product->getMagentoProductId();
                    break;
                case self::CATEGORY_NOT_SET:
                    $categoryNotSet[] = $product->getMagentoProductId();
                    break;
            }
        }

        return $this->makeResponse(
            $succeedProducersIds,
            $categoryNotSet,
            $requiredAttributesNotSet,
        );
    }

    private function validateCategory(?int $templateCategoryId)
    {
        if ($templateCategoryId === null) {
            return self::CATEGORY_NOT_SET;
        }

        if (isset($this->cachedCategoryResult[$templateCategoryId])) {
            return $this->cachedCategoryResult[$templateCategoryId];
        }

        $category = $this->categoryDictionaryRepository->get($templateCategoryId);
        if (!$category->hasRequiredProductAttributes()) {
            return $this->cachedCategoryResult[$templateCategoryId] = self::CATEGORY_AND_ATTRIBUTES_VALID;
        }

        $requiredAttributes = [];

        foreach ($category->getProductAttributes() as $productAttribute) {
            if ($productAttribute->isRequired() && !$productAttribute->getParentTemplatePid()) {
                $requiredAttributes[] = $productAttribute;
            }
        }

        //TODO: add validation to check sales attribute?
        $filledAttributes = 0;
        foreach ($requiredAttributes as $requiredAttribute) {
            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('category_dictionary_id', ['eq' => $category->getId()]);
            $collection->addFieldToFilter('attribute_type', ['eq' => $requiredAttribute->getType()]);
            $collection->addFieldToFilter('attribute_id', ['eq' => $requiredAttribute->getId()]);
            $collection->addFieldToFilter('value_mode', ['neq' => 0]);

            $filledAttributes += $collection->getSize();
        }

        if ($filledAttributes !== count($requiredAttributes)) {
            return $this->cachedCategoryResult[$templateCategoryId] = self::REQUIRED_ATTRIBUTES_NOT_SET;
        }

        return $this->cachedCategoryResult[$templateCategoryId] = self::CATEGORY_AND_ATTRIBUTES_VALID;
    }

    private function makeResponse(array $succeedProducersIds, array $categoryNotSet, array $requiredAttributesNotSet)
    {
        $categoryNotSetCount = count($categoryNotSet);
        $requiredAttributesNotSetCount = count($requiredAttributesNotSet);
        $succeedProducersIdsCount = count($succeedProducersIds);

        $message = '';

        if ($categoryNotSetCount > 0) {
            $message .= __('Temu Category is not set for some Products.');
        }

        if ($requiredAttributesNotSetCount > 0) {
            $message .= ' ' . __('Required Attributes are not set for the selected Temu Shop Category');
        }

        $this->setJsonContent([
            'validation' => ($categoryNotSetCount + $requiredAttributesNotSetCount) === 0,
            'total_count' => $categoryNotSetCount + $requiredAttributesNotSetCount + $succeedProducersIdsCount,
            'failed_count' => $categoryNotSetCount + $requiredAttributesNotSetCount,
            'failed_products' => $categoryNotSet,
            'message' => trim($message),
        ]);

        return $this->getResult();
    }
}
