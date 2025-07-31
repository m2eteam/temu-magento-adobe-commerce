<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\VariantSku;

class Specification
{
    private string $title;
    /** @var \M2E\Temu\Model\UnmanagedProduct\VariantSku\Specific[] */
    private array $specific;

    /**
     * @param string $title
     * @param Specific[] $specific
     */
    public function __construct(string $title, array $specific)
    {
        $this->title = $title;
        $this->specific = $specific;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSpecific(): array
    {
        return $this->specific;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'specific' => array_map(function (Specific $specific) {
                return [
                    'id' => $specific->getId(),
                    'title' => $specific->getTitle(),
                    'value_id' => $specific->getValueId(),
                    'value_title' => $specific->getValueTitle(),
                ];
            }, $this->specific)
        ];
    }
}
