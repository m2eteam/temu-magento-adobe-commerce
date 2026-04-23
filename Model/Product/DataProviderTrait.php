<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product;

trait DataProviderTrait
{
    /** @var \M2E\Temu\Model\Product\DataProvider\DataBuilderInterface[] */
    private array $dataBuilders = [];

    /** @var \M2E\Temu\Model\Product\DataProvider\AbstractResult[] */
    private array $results = [];

    /**
     * @return string[]
     */
    public function getLogs(): array
    {
        $result = [];
        foreach ($this->dataBuilders as $dataBuilder) {
            $message = $dataBuilder->getWarningMessages();
            if (empty($message)) {
                continue;
            }

            array_push($result, ...$message);
        }

        return $result;
    }

    // ----------------------------------------

    private function getBuilder(
        string $builderNick,
        string $builderAlias = ''
    ): \M2E\Temu\Model\Product\DataProvider\DataBuilderInterface {
        if (empty($builderAlias)) {
            $builderAlias = $builderNick;
        }

        if (isset($this->dataBuilders[$builderAlias])) {
            return $this->dataBuilders[$builderAlias];
        }

        return $this->dataBuilders[$builderAlias] = $this->dataBuilderFactory->create($builderNick);
    }

    private function addResult(string $builderAlias, DataProvider\AbstractResult $result): void
    {
        $this->results[$builderAlias] = $result;
    }

    private function hasResult(string $builderAlias): bool
    {
        return isset($this->results[$builderAlias]);
    }

    private function getResult(string $builderAlias): \M2E\Temu\Model\Product\DataProvider\AbstractResult
    {
        return $this->results[$builderAlias];
    }
}
