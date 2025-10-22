<?php
declare(strict_types=1);

namespace App\DTOs;

class GuestAddressDTO
{
    public function __construct(
        public readonly string $country,
        public readonly string $countryCode,
        public readonly string $city,
        public readonly string $street,
        public readonly string $zipCode,
    ) {}
}
