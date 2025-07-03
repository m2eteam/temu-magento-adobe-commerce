<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider;

class BulletPointsProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'BulletPoints';

    private ?string $bulletPointsHash = null;

    public function getBulletPoints(\M2E\Temu\Model\Product $product): array
    {
        $values = [];

        $descriptionTemplate = $product->getDescriptionTemplate();
        $bulletPoints = $descriptionTemplate->getBulletPoints();

        foreach ($bulletPoints as $bulletPoint) {
            if ($bulletPoint->isModeCustomValue()) {
                $value = trim($bulletPoint->getCustomValue());

                if ($value === '') {
                    continue;
                }

                $values[] = $value;
            } elseif ($bulletPoint->isModeCustomAttribute()) {
                $attribute = $bulletPoint->getAttribute();
                $attributeValue = $product->getMagentoProduct()->getAttributeValue($attribute);

                if (empty($attributeValue)) {
                    continue;
                }

                $values[] = $attributeValue;
            }
        }

        if (!empty($values)) {
            $this->bulletPointsHash = $this->generateBulletPointsHash($values);
        }

        return $values;
    }

    public function getMetaData(): array
    {
        return [
            self::NICK => $this->bulletPointsHash,
        ];
    }

    private function generateBulletPointsHash(array $bulletPoints): string
    {
        sort($bulletPoints);

        return \M2E\Core\Helper\Data::md5String(json_encode($bulletPoints));
    }
}
