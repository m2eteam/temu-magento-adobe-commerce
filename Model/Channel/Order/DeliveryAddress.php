<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Order;

class DeliveryAddress
{
    public ?string $name;
    public ?string $addressLineAll;
    public ?string $line1;
    public ?string $line2;
    public ?string $line3;
    public ?string $country;
    public ?string $city;
    public ?string $postCode;
    public ?string $countryCode;
    public ?string $state;

    public function __construct(
        ?string $name,
        ?string $addressLineAll,
        ?string $line1,
        ?string $line2,
        ?string $line3,
        ?string $country,
        ?string $city,
        ?string $postCode,
        ?string $countryCode,
        ?string $state
    ) {
        $this->name = $name;
        $this->addressLineAll = $addressLineAll;
        $this->line1 = $line1;
        $this->line2 = $line2;
        $this->line3 = $line3;
        $this->country = $country;
        $this->city = $city;
        $this->postCode = $postCode;
        $this->countryCode = $countryCode;
        $this->state = $state;
    }
}
