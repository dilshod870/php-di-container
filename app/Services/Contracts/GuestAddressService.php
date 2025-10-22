<?php
declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\GuestAddressDTO;
use App\Models\Guest;

interface GuestAddressService
{
    public function getGuestCountry(Guest $guest): string;
    public function getGuestCity(Guest $guest): string;
    public function getGuestCountryCode(Guest $guest): string;
    public function getGuestAddressInfo(Guest $guest): GuestAddressDTO;
}
