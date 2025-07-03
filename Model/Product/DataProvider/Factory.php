<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider;

class Factory implements FactoryInterface
{
    private const ALLOWED_BUILDERS = [
        VariantsProvider::NICK => VariantsProvider::class,
        ShippingProvider::NICK => ShippingProvider::class,
        DescriptionProvider::NICK => DescriptionProvider::class,
        TitleProvider::NICK => TitleProvider::class,
        ImagesProvider::NICK => ImagesProvider::class,
        CategoryProvider::NICK => CategoryProvider::class,
        ProductAttributesProvider::NICK => ProductAttributesProvider::class,
        BulletPointsProvider::NICK => BulletPointsProvider::class,
        ItemTaxCodeProvider::NICK => ItemTaxCodeProvider::class,
    ];

    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $nick): DataBuilderInterface
    {
        if (!isset(self::ALLOWED_BUILDERS[$nick])) {
            throw new \M2E\Temu\Model\Exception\Logic(sprintf('Unknown builder - %s', $nick));
        }

        /** @var DataBuilderInterface */
        return $this->objectManager->create(self::ALLOWED_BUILDERS[$nick]);
    }
}
