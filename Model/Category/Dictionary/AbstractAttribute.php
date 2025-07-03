<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

abstract class AbstractAttribute
{
    private string $id;
    private string $name;
    private bool $isRequired;
    private bool $isMultipleSelected;
    /** @var \M2E\Temu\Model\Category\Dictionary\Attribute\Value[] */
    private array $recommendedValues;
    private bool $isSale;
    private int $refPid;
    private int $templatePid;
    private ?int $parentSpecId;
    private bool $isCustomised;
    private string $typeFormat;
    private array $rules;
    private int $pid;
    private ?int $parentTemplatePid;
    private ?int $multipleSelectedMaxCount;

    public function __construct(
        string $id,
        string $name,
        bool $isSale,
        bool $isRequired,
        bool $isCustomised,
        bool $isMultipleSelected,
        string $typeFormat,
        array $rules,
        int $pid,
        int $refPid,
        int $templatePid,
        ?int $parentSpecId,
        ?int $parentTemplatePid,
        array $recommendedValues = [],
        ?int $multipleSelectedMaxCount = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->isRequired = $isRequired;
        $this->isMultipleSelected = $isMultipleSelected;
        $this->recommendedValues = $recommendedValues;
        $this->isSale = $isSale;
        $this->refPid = $refPid;
        $this->templatePid = $templatePid;
        $this->parentSpecId = $parentSpecId;
        $this->isCustomised = $isCustomised;
        $this->typeFormat = $typeFormat;
        $this->rules = $rules;
        $this->pid = $pid;
        $this->parentTemplatePid = $parentTemplatePid;
        $this->multipleSelectedMaxCount = $multipleSelectedMaxCount;
    }

    abstract public function getType(): string;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isMultipleSelected(): bool
    {
        return $this->isMultipleSelected;
    }

    /**
     * @return array|\M2E\Temu\Model\Category\Dictionary\Attribute\Value[]
     */
    public function getValues(): array
    {
        return $this->recommendedValues;
    }

    public function isSale(): bool
    {
        return $this->isSale;
    }

    public function getRefPid(): int
    {
        return $this->refPid;
    }

    public function getTemplatePid(): int
    {
        return $this->templatePid;
    }

    public function getParentSpecId(): ?int
    {
        return $this->parentSpecId;
    }

    public function isCustomised(): bool
    {
        return $this->isCustomised;
    }

    public function getTypeFormat(): string
    {
        return $this->typeFormat;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getParentTemplatePid(): ?int
    {
        return $this->parentTemplatePid;
    }

    public function getMultipleSelectedMaxCount(): ?int
    {
        return $this->multipleSelectedMaxCount;
    }
}
