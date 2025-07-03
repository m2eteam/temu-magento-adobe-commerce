<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy;

interface PolicyInterface
{
    public function getId(): ?int;
    public function getNick(): string;
    public function setTitle(string $title): void;
    public function getTitle(): string;
}
