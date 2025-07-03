<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Validator;

class ValidatorMessage
{
    public const TYPE_ERROR = 'error';

    private string $text;
    private string $code;
    private string $type;

    public function __construct(string $text, string $code, string $type = self::TYPE_ERROR)
    {
        $this->text = $text;
        $this->code = $code;
        $this->type = $type;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isError(): bool
    {
        return $this->type === self::TYPE_ERROR;
    }
}
