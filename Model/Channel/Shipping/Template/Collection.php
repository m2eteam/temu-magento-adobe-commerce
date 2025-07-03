<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Shipping\Template;

use M2E\Temu\Model\Channel\Shipping\Template;

class Collection
{
    /** @var \M2E\Temu\Model\Channel\Shipping\Template[] */
    private array $deliveryTemplates = [];

    public function add(\M2E\Temu\Model\Channel\Shipping\Template $deliveryTemplate): self
    {
        $this->deliveryTemplates[$deliveryTemplate->id] = $deliveryTemplate;

        return $this;
    }

    public function has(?string $id): bool
    {
        return isset($this->deliveryTemplates[$id]);
    }

    public function get(string $id): \M2E\Temu\Model\Channel\Shipping\Template
    {
        return $this->deliveryTemplates[$id];
    }

    public function isEmpty(): bool
    {
        return empty($this->deliveryTemplates);
    }

    /**
     * @return \M2E\Temu\Model\Channel\Shipping\Template[]
     */
    public function getAll(): array
    {
        return array_values($this->deliveryTemplates);
    }
}
