<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Description;

class BulletPoint
{
    public const MODE_NOT_CONFIGURED = 0;
    public const MODE_CUSTOM_VALUE = 1;
    public const MODE_CUSTOM_ATTRIBUTE = 2;
    public const MAX_COUNT = 5;

    private int $mode;
    private ?string $customValue;
    private ?string $attribute;

    public function __construct(
        int $mode,
        ?string $customValue,
        ?string $attribute
    ) {
        $this->mode = $mode;
        $this->customValue = $customValue;
        $this->attribute = $attribute;
    }

    public function isConfigured(): bool
    {
        return ($this->isModeCustomAttribute() && !empty($this->getAttribute()))
            || ($this->isModeCustomValue() && !empty($this->getCustomValue()));
    }

    public function isModeCustomValue(): bool
    {
        return $this->mode === self::MODE_CUSTOM_VALUE;
    }

    public function isModeCustomAttribute(): bool
    {
        return $this->mode === self::MODE_CUSTOM_ATTRIBUTE;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getCustomValue(): ?string
    {
        return $this->customValue;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }
}
