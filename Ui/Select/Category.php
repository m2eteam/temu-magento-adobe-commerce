<?php

declare(strict_types=1);

namespace M2E\Temu\Ui\Select;

use Magento\Framework\Data\OptionSourceInterface;
use M2E\Temu\Model\Category\Dictionary\Repository;

class Category implements OptionSourceInterface
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->repository->getAllItems() as $dictionary) {
            $options[] = [
                'label' => $this->formatLabel($dictionary),
                'value' => $dictionary->getId(),
            ];
        }

        return $options;
    }

    private function formatLabel(\M2E\Temu\Model\Category\Dictionary $dictionary): string
    {
        return sprintf(
            '%s (%s) (%s)',
            $dictionary->getTitle(),
            $dictionary->getCategoryId(),
            ucfirst($dictionary->getRegion())
        );
    }
}
