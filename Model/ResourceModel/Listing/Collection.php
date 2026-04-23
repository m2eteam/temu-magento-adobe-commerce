<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Listing;

/**
 * @method \M2E\Temu\Model\Listing[] getItems()
 * @method \M2E\Temu\Model\Listing[] getFirstItem()
 */
class Collection extends \M2E\Temu\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \M2E\Temu\Model\Listing::class,
            \M2E\Temu\Model\ResourceModel\Listing::class
        );
    }
}
