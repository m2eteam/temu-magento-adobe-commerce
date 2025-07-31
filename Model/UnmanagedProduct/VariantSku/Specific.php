<?php

declare(strict_types=1);

namespace M2E\Temu\Model\UnmanagedProduct\VariantSku;

class Specific
{
    private string $id;
    private string $title;
    private string $valueId;
    private string $valueTitle;

    public function __construct(string $id, string $title, string $valueId, string $valueTitle)
    {
        $this->id = $id;
        $this->title = $title;
        $this->valueId = $valueId;
        $this->valueTitle = $valueTitle;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getValueId(): string
    {
        return $this->valueId;
    }

    public function getValueTitle(): string
    {
        return $this->valueTitle;
    }
}
