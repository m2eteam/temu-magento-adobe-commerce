<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\VariantSku;

use M2E\Temu\Model\Product\DataProvider\AbstractResult;
use M2E\Temu\Model\Product\DataProviderTrait;

class DataProvider
{
    use DataProviderTrait;

    private \M2E\Temu\Model\Product\VariantSku\DataProvider\Factory $dataBuilderFactory;
    private \M2E\Temu\Model\Product\VariantSku $variantSku;
    private \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings;

    public function __construct(
        \M2E\Temu\Model\Product\VariantSku $variantSku,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings,
        \M2E\Temu\Model\Product\VariantSku\DataProvider\Factory $dataBuilderFactory
    ) {
        $this->variantSku = $variantSku;
        $this->variantSettings = $variantSettings;
        $this->dataBuilderFactory = $dataBuilderFactory;
    }

    // ----------------------------------------

    public function getPrice(): DataProvider\Price\Result
    {
        if ($this->hasResult(DataProvider\PriceProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Price\Result */
            return $this->getResult(DataProvider\PriceProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\PriceProvider $builder */
        $builder = $this->getBuilder(DataProvider\PriceProvider::NICK);

        $value = $builder->getPrice($this->variantSku);

        $result = DataProvider\Price\Result::success($value);

        $this->addResult(DataProvider\PriceProvider::NICK, $result);

        return $result;
    }

    public function getQty(): DataProvider\Qty\Result
    {
        if ($this->hasResult(DataProvider\QtyProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Qty\Result */
            return $this->getResult(DataProvider\QtyProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\QtyProvider $builder */
        $builder = $this->getBuilder(DataProvider\QtyProvider::NICK);

        $value = $builder->getQty($this->variantSku);

        $result = DataProvider\Qty\Result::success($value, $builder->getWarningMessages());

        $this->addResult(DataProvider\QtyProvider::NICK, $result);

        return $result;
    }

    public function getImages(): DataProvider\Images\Result
    {
        if ($this->hasResult(DataProvider\ImageProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Images\Result */
            return $this->getResult(DataProvider\ImageProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\ImageProvider $builder */
        $builder = $this->getBuilder(DataProvider\ImageProvider::NICK);

        $value = $builder->getImage($this->variantSku);

        $result = DataProvider\Images\Result::success($value, $builder->getWarningMessages());

        $this->addResult(DataProvider\ImageProvider::NICK, $result);

        return $result;
    }

    public function getIdentifier(): DataProvider\Identifier\Result
    {
        if ($this->hasResult(DataProvider\IdentifierProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Identifier\Result */
            return $this->getResult(DataProvider\IdentifierProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\IdentifierProvider $builder */
        $builder = $this->getBuilder(DataProvider\IdentifierProvider::NICK);

        $value = $builder->getIdentifier($this->variantSku);

        $result = DataProvider\Identifier\Result::success($value, $builder->getWarningMessages());

        $this->addResult(DataProvider\IdentifierProvider::NICK, $result);

        return $result;
    }

    public function getReferenceLink(): DataProvider\ReferenceLink\Result
    {
        if ($this->hasResult(DataProvider\ReferenceLinkProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\ReferenceLink\Result */
            return $this->getResult(DataProvider\ReferenceLinkProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\ReferenceLinkProvider $builder */
        $builder = $this->getBuilder(DataProvider\ReferenceLinkProvider::NICK);

        $value = $builder->getReferenceLink($this->variantSku);

        $result = DataProvider\ReferenceLink\Result::success($value, $builder->getWarningMessages());

        $this->addResult(DataProvider\ReferenceLinkProvider::NICK, $result);

        return $result;
    }

    public function getPackage(): DataProvider\Package\Result
    {
        if ($this->hasResult(DataProvider\PackageProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Package\Result */
            return $this->getResult(DataProvider\PackageProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\PackageProvider $builder */
        $builder = $this->getBuilder(DataProvider\PackageProvider::NICK);

        $value = $builder->getPackage($this->variantSku);

        $result = DataProvider\Package\Result::success($value, $builder->getWarningMessages());

        $this->addResult(DataProvider\PackageProvider::NICK, $result);

        return $result;
    }

    public function getSalesAttributesData(): \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\Result
    {
        if ($this->hasResult(DataProvider\SalesAttributesProvider::NICK)) {
            /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\Result */
            return $this->getResult(DataProvider\SalesAttributesProvider::NICK);
        }

        /** @var \M2E\Temu\Model\Product\VariantSku\DataProvider\SalesAttributesProvider $builder */
        $builder = $this->getBuilder(DataProvider\SalesAttributesProvider::NICK);
        $value = $builder->getSalesAttributesData($this->variantSku);
        $result = \M2E\Temu\Model\Product\VariantSku\DataProvider\Attributes\Result::success(
            $value,
            $builder->getWarningMessages()
        );

        $this->addResult(DataProvider\SalesAttributesProvider::NICK, $result);

        return $result;
    }

    private function getBuilder(
        string $nick
    ): \M2E\Temu\Model\Product\DataProvider\DataBuilderInterface {
        if (isset($this->dataBuilders[$nick])) {
            return $this->dataBuilders[$nick];
        }

        return $this->dataBuilders[$nick] = $this->dataBuilderFactory->create($nick);
    }
}
