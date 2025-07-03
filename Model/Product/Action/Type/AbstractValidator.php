<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Type;

use M2E\Core\Model\Response\Message;
use M2E\Temu\Model\Product\Action\Configurator;
use M2E\Temu\Model\Product\Action\Validator\ValidatorMessage;

abstract class AbstractValidator
{
    private array $params = [];
    private array $messages = [];
    private Configurator $configurator;
    private \M2E\Temu\Model\Product $listingProduct;

    public function init(
        \M2E\Temu\Model\Product $listingProduct,
        Configurator $configurator,
        $params
    ): void {
        $this->listingProduct = $listingProduct;
        $this->configurator = $configurator;
        $this->params = $params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    protected function getParams(): array
    {
        return $this->params;
    }

    public function setConfigurator(Configurator $configurator): self
    {
        $this->configurator = $configurator;

        return $this;
    }

    protected function getConfigurator(): Configurator
    {
        return $this->configurator;
    }

    public function setListingProduct(\M2E\Temu\Model\Product $listingProduct): self
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    protected function getListingProduct(): \M2E\Temu\Model\Product
    {
        return $this->listingProduct;
    }

    abstract public function validate(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings
    ): bool;

    protected function addMessage(ValidatorMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return ValidatorMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function hasErrorMessages(): bool
    {
        foreach ($this->getMessages() as $message) {
            if ($message->isError()) {
                return true;
            }
        }

        return false;
    }
}
