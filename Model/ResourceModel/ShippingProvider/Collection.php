<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\ShippingProvider;

use M2E\Temu\Model\ResourceModel\ShippingProvider as ShippingProviderResource;

/**
 * @method \M2E\Temu\Model\ShippingProvider getFirstItem()
 * @method \M2E\Temu\Model\ShippingProvider[] getItems()
 */
class Collection extends \M2E\Temu\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Model\ShippingProvider::class,
            ShippingProviderResource::class
        );
    }
}
