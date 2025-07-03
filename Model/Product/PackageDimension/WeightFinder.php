<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\PackageDimension;

class WeightFinder extends AbstractDimensionFinder
{
    public const WEIGHT_DIMENSION_LB = 'lb';
    public const WEIGHT_DIMENSION_G = 'g';

    /**
     * @throws \M2E\Temu\Model\Product\PackageDimension\PackageDimensionException
     */
    public function find(\M2E\Temu\Model\Product $product): Weight
    {
        $unit = $this->getUnit($product);

        try {
            $weight = $this->getPackageWeight($product->getMagentoProduct());

            if ($unit === self::WEIGHT_DIMENSION_G) {
                $weight = $this->convertPackageWeightInGram($weight);
            }

            return new Weight(
                $weight,
                $unit
            );
        } catch (NotConfiguredException $exception) {
            throw $exception;
        } catch (PackageDimensionException $exception) {
        }

        $childWeights = [];
        foreach ($product->getVariants() as $variant) {
            try {
                $weight = $this->getPackageWeight($variant->getMagentoProduct());

                if ($unit === self::WEIGHT_DIMENSION_G) {
                    $weight = $this->convertPackageWeightInGram($weight);
                }

                $childWeight = new Weight(
                    $weight,
                    $unit
                );
                $childWeights[(string) $childWeight->getValue()] = $childWeight;
            } catch (PackageDimensionException $exception) {
                continue;
            }
        }

        if (empty($childWeights)) {
            throw new PackageDimensionException(
                (string)__('Package Weight is missing. To list the Product, please make sure that the Package settings are correct.'),
                [],
                \M2E\Temu\Model\Product\PackageDimension\PackageDimensionException::CODE_WEIGHT_MISSING
            );
        }

        krsort($childWeights, SORT_NUMERIC);

        return reset($childWeights);
    }

    /**
     * @throws \M2E\Temu\Model\Product\PackageDimension\NotConfiguredException
     * @throws \M2E\Temu\Model\Product\PackageDimension\NotFoundAttributeValueException
     */
    public function getPackageWeight(\M2E\Temu\Model\Magento\Product $magentoProduct): float
    {
        return $this->toFloat(
            $this->getPackageDimensionValue(
                \M2E\Temu\Model\Settings::DIMENSION_TYPE_WEIGHT,
                $magentoProduct
            )
        );
    }

    private function convertPackageWeightInGram(float $value): float
    {
        return $value * 1000;
    }

    private function getUnit(\M2E\Temu\Model\Product $product): string
    {
        if ($product->getAccount()->isRegionUs()) {
            return self::WEIGHT_DIMENSION_LB;
        }

        return self::WEIGHT_DIMENSION_G;
    }
}
