<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Tag;

class BlockingErrors
{
    public function getList(): array
    {
        return [
            '150011010', // The keyword attribute [...] is required, please fill in accurately and appropriately
            '150010202', // Invalid unit for weight/Invalid unit for volume
            '150011000', // Attribute or Specification Error: Please reset the variants template and enter the required variants...
            '150010027', // Variant too long
            '1320432631', // Sku has no price order
            '150010011', // Only use letters, numbers and common punctuation for product name
            '150010052', // Rich text not supported
            '150010020', // Upload 3 to 10 images
            '150010090', // SKU duplicated
            '150011010', // The keyword attribute [...] is required, please fill in accurately and appropriately
            '150011019', // The input ... is incorrect, please modify it.
        ];
    }
}
