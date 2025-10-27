<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Connector;

class Protocol implements \M2E\Core\Model\Connector\ProtocolInterface
{
    public const COMPONENT_NAME = 'Temu';
    public const COMPONENT_VERSION = 6;

    public function getComponent(): string
    {
        return self::COMPONENT_NAME;
    }

    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
