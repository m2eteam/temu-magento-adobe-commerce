<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy;

abstract class AbstractBuilder
{
    public function build(
        \M2E\Temu\Model\Policy\PolicyInterface $model,
        array $rawData
    ): void {
        if (empty($rawData)) {
            return;
        }

        $id = $this->findId($rawData);
        if ($id !== null) {
            /** @psalm-suppress UndefinedInterfaceMethod */
            $model->setId($id);
        }

        $title = $rawData['title'];
        $model->setTitle($title);

        unset($rawData['id'], $rawData['title']);

        $rawData = \M2E\Core\Helper\Data::arrayReplaceRecursive($this->getDefaultData(), $rawData);

        $this->initData($model, $id, $title, $rawData);
    }

    abstract protected function initData(
        \M2E\Temu\Model\Policy\PolicyInterface $model,
        ?int $id,
        string $title,
        array $rawData
    ): void;

    abstract public function getDefaultData(): array;

    private function findId(array $rawData): ?int
    {
        if (!isset($rawData['id'])) {
            return null;
        }

        $id = (int)$rawData['id'];
        if ($id <= 0) {
            return null;
        }

        return $id;
    }
}
