<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Listing;

class ProductTotalsSelect
{
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \M2E\Temu\Model\ResourceModel\Listing $listingResource;
    private \M2E\Temu\Model\ResourceModel\Product $productResource;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \M2E\Temu\Model\ResourceModel\Listing $listingResource,
        \M2E\Temu\Model\ResourceModel\Product $productResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->listingResource = $listingResource;
        $this->productResource = $productResource;
    }

    public function getSelect(): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(
            ['listing' => $this->listingResource->getMainTable()],
            ['listing_id' => \M2E\Temu\Model\ResourceModel\Listing::COLUMN_ID]
        );

        $select->joinLeft(
            ['product' => $this->productResource->getMainTable()],
            sprintf(
                'listing.%s = product.%s',
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_ID,
                \M2E\Temu\Model\ResourceModel\Product::COLUMN_LISTING_ID
            ),
            [
                'products_total_count' => new \Zend_Db_Expr(
                    sprintf(
                        'IFNULL(COUNT(product.%s), 0)',
                        \M2E\Temu\Model\ResourceModel\Product::COLUMN_ID
                    )
                ),
                'products_active_count' => new \Zend_Db_Expr(
                    sprintf(
                        'IFNULL(COUNT(IF(product.%s = %s, product.%s, NULL)), 0)',
                        \M2E\Temu\Model\ResourceModel\Product::COLUMN_STATUS,
                        \M2E\Temu\Model\Product::STATUS_LISTED,
                        \M2E\Temu\Model\ResourceModel\Product::COLUMN_ID
                    )
                ),
                'products_inactive_count' => new \Zend_Db_Expr(
                    sprintf(
                        'IFNULL(COUNT(IF(product.%s != %s, product.%s, NULL)), 0)',
                        \M2E\Temu\Model\ResourceModel\Product::COLUMN_STATUS,
                        \M2E\Temu\Model\Product::STATUS_LISTED,
                        \M2E\Temu\Model\ResourceModel\Product::COLUMN_ID
                    )
                ),
            ]
        );

        $select->group(
            sprintf(
                'listing.%s',
                \M2E\Temu\Model\ResourceModel\Listing::COLUMN_ID
            )
        );

        return $select;
    }
}
