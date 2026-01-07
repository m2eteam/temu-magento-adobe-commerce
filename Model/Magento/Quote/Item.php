<?php

namespace M2E\Temu\Model\Magento\Quote;

use M2E\Temu\Model\Magento\Quote\Total\RoundTaxPercent;

class Item extends \Magento\Framework\DataObject
{
    private ?\Magento\Catalog\Model\Product $product = null;
    private ?\Magento\GiftMessage\Model\Message $giftMessage = null;

    private \M2E\Temu\Model\Magento\Tax\Helper $taxHelper;
    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private \Magento\Tax\Model\Calculation $calculation;
    private \Magento\GiftMessage\Model\MessageFactory $messageFactory;
    private \Magento\Quote\Model\Quote $quote;
    private \M2E\Temu\Model\Order\Item\ProxyObject $proxyItem;
    private \M2E\Temu\Model\Magento\Tax\Rule\BuilderFactory $taxRuleBuilderFactory;
    private \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory;

    public function __construct(
        \M2E\Temu\Model\Magento\Tax\Rule\BuilderFactory $taxRuleBuilderFactory,
        \M2E\Temu\Model\Magento\ProductFactory $ourMagentoProductFactory,
        \M2E\Temu\Model\Magento\Tax\Helper $taxHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\Quote\Model\Quote $quote,
        \M2E\Temu\Model\Order\Item\ProxyObject $proxyItem
    ) {
        parent::__construct();
        $this->taxHelper = $taxHelper;
        $this->productFactory = $productFactory;
        $this->calculation = $calculation;
        $this->messageFactory = $messageFactory;
        $this->quote = $quote;
        $this->proxyItem = $proxyItem;
        $this->taxRuleBuilderFactory = $taxRuleBuilderFactory;
        $this->ourMagentoProductFactory = $ourMagentoProductFactory;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \M2E\Temu\Model\Exception
     */
    public function getProduct(): \Magento\Catalog\Model\Product
    {
        if ($this->product !== null) {
            return $this->product;
        }

        if ($this->proxyItem->getMagentoProduct()->isGroupedType()) {
            $this->product = $this->getAssociatedGroupedProduct();

            if ($this->product === null) {
                throw new \M2E\Temu\Model\Exception(
                    'There are no associated Products found for Grouped Product.'
                );
            }
        } else {
            $this->product = $this->proxyItem->getProduct();

            if ($this->proxyItem->getMagentoProduct()->isBundleType()) {
                $this->product->setPriceType(\Magento\Catalog\Model\Product\Type\AbstractType::CALCULATE_PARENT);
            }
        }

        // tax class id should be set before price calculation
        return $this->setTaxClassIntoProduct($this->product);
    }

    // ---------------------------------------

    private function getAssociatedGroupedProduct(): ?\Magento\Catalog\Model\Product
    {
        $associatedProducts = $this->proxyItem->getAssociatedProducts();
        $associatedProductId = reset($associatedProducts);

        $product = $this->productFactory
            ->create()
            ->setStoreId($this->quote->getStoreId())
            ->load($associatedProductId);

        return $product->getId() ? $product : null;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function setTaxClassIntoProduct(
        \Magento\Catalog\Model\Product $product
    ): \Magento\Catalog\Model\Product {
        $proxyOrder = $this->proxyItem->getProxyOrder();
        $itemTaxRate = $this->getTaxRateOfProxyItem();
        $isOrderHasTax = $this->proxyItem->getProxyOrder()->hasTax();
        $hasRatesForCountry = $this->taxHelper->hasRatesForCountry($this->quote->getShippingAddress()->getCountryId());
        $calculationBasedOnOrigin = $this->taxHelper->isCalculationBasedOnOrigin($this->quote->getStore());

        if (
            $proxyOrder->isTaxModeNone()
            || ($proxyOrder->isTaxModeChannel() && $itemTaxRate <= 0)
            || ($proxyOrder->isTaxModeMagento() && !$hasRatesForCountry && !$calculationBasedOnOrigin)
            || ($proxyOrder->isTaxModeMixed() && $itemTaxRate <= 0 && $isOrderHasTax)
        ) {
            return $product->setTaxClassId(\M2E\Temu\Model\Magento\Product::TAX_CLASS_ID_NONE);
        }

        if (
            $proxyOrder->isTaxModeMagento()
            || $itemTaxRate <= 0
            || $itemTaxRate == $this->getProductTaxRate($product->getTaxClassId())
        ) {
            return $product;
        }

        // Create tax rule according to channel tax rate
        // ---------------------------------------
        $taxRuleBuilder = $this->taxRuleBuilderFactory->create();
        $taxRuleBuilder->buildProductTaxRule(
            $itemTaxRate,
            $this->quote->getShippingAddress()->getCountryId(),
            $this->quote->getCustomerTaxClassId()
        );

        $taxRule = $taxRuleBuilder->getRule();
        $productTaxClasses = $taxRule->getProductTaxClasses();

        // ---------------------------------------

        return $product->setTaxClassId(array_shift($productTaxClasses));
    }

    /**
     * @return float|int
     */
    private function getTaxRateOfProxyItem()
    {
        $productPriceTaxRateObject = $this->proxyItem->getProductPriceTaxRateObject();
        if (empty($productPriceTaxRateObject)) {
            return $this->proxyItem->getTaxRate();
        }

        $rateValue = $productPriceTaxRateObject->getValue();
        if (!$productPriceTaxRateObject->isEnabledRoundingOfValue()) {
            return $rateValue;
        }

        $notRoundedTaxRateValue = $productPriceTaxRateObject->getNotRoundedValue();
        if ($rateValue !== $notRoundedTaxRateValue) {
            $this->quote->setData(
                RoundTaxPercent::PRODUCT_PRICE_TAX_DATA_KEY,
                $productPriceTaxRateObject
            );
        }

        return $notRoundedTaxRateValue;
    }

    private function getProductTaxRate($productTaxClassId)
    {
        $taxCalculator = $this->calculation;

        $request = $taxCalculator->getRateRequest(
            $this->quote->getShippingAddress(),
            $this->quote->getBillingAddress(),
            $this->quote->getCustomerTaxClassId(),
            $this->quote->getStore()
        );
        $request->setProductClassId($productTaxClassId);

        return $taxCalculator->getRate($request);
    }

    //########################################

    public function getRequest()
    {
        $request = new \Magento\Framework\DataObject();
        $request->setQty($this->proxyItem->getQty());

        // grouped and downloadable products doesn't have options
        if (
            $this->proxyItem->getMagentoProduct()->isGroupedType() ||
            $this->proxyItem->getMagentoProduct()->isDownloadableType()
        ) {
            return $request;
        }

        $magentoProduct = $this->ourMagentoProductFactory->create()->setProduct($this->getProduct());
        $options = $this->proxyItem->getOptions();

        if (empty($options)) {
            return $request;
        }

        if ($magentoProduct->isSimpleType()) {
            $request->setOptions($options);
        } elseif ($magentoProduct->isBundleType()) {
            $request->setBundleOption($options);
        } elseif ($magentoProduct->isConfigurableType()) {
            $request->setSuperAttribute($options);
        } elseif ($magentoProduct->isDownloadableType()) {
            $request->setLinks($options);
        }

        return $request;
    }

    //########################################

    public function getGiftMessageId()
    {
        $giftMessage = $this->getGiftMessage();

        return $giftMessage ? $giftMessage->getId() : null;
    }

    public function getGiftMessage(): ?\Magento\GiftMessage\Model\Message
    {
        if ($this->giftMessage !== null) {
            return $this->giftMessage;
        }

        $giftMessageData = $this->proxyItem->getGiftMessage();

        if (!is_array($giftMessageData)) {
            return null;
        }

        $giftMessageData['customer_id'] = (int)$this->quote->getCustomerId();
        $giftMessage = $this->messageFactory->create()->addData($giftMessageData);

        if ($giftMessage->isMessageEmpty()) {
            return null;
        }

        $this->giftMessage = $giftMessage->save();

        return $this->giftMessage;
    }

    //########################################

    public function getAdditionalData(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $additionalData = $this->proxyItem->getAdditionalData();
        $existAdditionalData = is_string($quoteItem->getAdditionalData())
            ? json_decode($quoteItem->getAdditionalData(), true)
            : [];

        return json_encode(array_merge($existAdditionalData, $additionalData), JSON_THROW_ON_ERROR);
    }

    //########################################
}
