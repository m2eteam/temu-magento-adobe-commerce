<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Shipping;

class Template
{
    public string $id;
    public string $name;

    public function __construct(
        string $id,
        string $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }
}
