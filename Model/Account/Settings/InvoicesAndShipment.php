<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Account\Settings;

class InvoicesAndShipment
{
    private bool $isCreateMagentoInvoice = true;
    private bool $isCreateMagentoShipment = true;
    private bool $isMapShippingProviderByCustomCarrierTitle = false;

    public function isCreateMagentoInvoice(): bool
    {
        return $this->isCreateMagentoInvoice;
    }

    public function createWithMagentoInvoice(bool $status): self
    {
        $new = clone $this;
        $new->isCreateMagentoInvoice = $status;

        return $new;
    }

    // ----------------------------------------

    public function isCreateMagentoShipment(): bool
    {
        return $this->isCreateMagentoShipment;
    }

    public function createWithMagentoShipment(bool $status): self
    {
        $new = clone $this;
        $new->isCreateMagentoShipment = $status;

        return $new;
    }

    // ----------------------------------------

    public function isMapShippingProviderByCustomCarrierTitle(): bool
    {
        return $this->isMapShippingProviderByCustomCarrierTitle;
    }

    public function createWithMapShippingProviderByCustomCarrierTitle(bool $status): self
    {
        $new = clone $this;
        $new->isMapShippingProviderByCustomCarrierTitle = $status;

        return $new;
    }
}
