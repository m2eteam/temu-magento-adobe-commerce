<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Category\Attribute;

class Result
{
    private bool $isAllCompleted;
    private int $processedProductCount;
    private int $errorProductCount;
    private int $totalProductCount;

    public function __construct(
        bool $isAllCompleted,
        int $processedProductCount,
        int $errorProductCount,
        int $totalProductCount
    ) {
        $this->isAllCompleted = $isAllCompleted;
        $this->processedProductCount = $processedProductCount;
        $this->errorProductCount = $errorProductCount;
        $this->totalProductCount = $totalProductCount;
    }

    public function isAllCompleted(): bool
    {
        return $this->isAllCompleted;
    }

    public function getProcessedProductCount(): int
    {
        return $this->processedProductCount;
    }

    public function getErrorProductCount(): int
    {
        return $this->errorProductCount;
    }

    public function getTotalProductCount(): int
    {
        return $this->totalProductCount;
    }
}
