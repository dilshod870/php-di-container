<?php
declare(strict_types=1);

namespace App\Services;

use App\DTOs\GuestAddressDTO;
use App\Exceptions\InvalidAddressException;
use App\Exceptions\ProfileNotFoundException;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\Guest;
use App\Services\Contracts\GuestAddressService;
use App\Support\CacheInterface;
use App\Support\LoggerInterface;
use App\Support\NullCache;
use App\Support\NullLogger;

class GuestAddressServiceImpl implements GuestAddressService
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly CacheInterface $cache = new NullCache(),
        private readonly int $ttl = 3600,
    ) {}

    public function getGuestCountry(Guest $guest): string
    {
        $cacheKey = "guest_{$guest->getId()}_country";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Country (cache) for guest {$guest->getId()}");
            return $cached;
        }

        $country = $this->getCountry($guest);
        $name = $country->getName();

        if ($name === '') {
            throw new InvalidAddressException('Empty country name');
        }

        $this->cache->set($cacheKey, $name, $this->ttl);
        return $name;
    }

    public function getGuestCity(Guest $guest): string
    {
        $cacheKey = "guest_{$guest->getId()}_city";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("City (cache) for guest {$guest->getId()}");
            return $cached;
        }

        $city = $this->getCity($guest);
        $name = $city->getName();

        if ($name === '') {
            throw new InvalidAddressException('Empty city name');
        }

        $this->cache->set($cacheKey, $name, $this->ttl);
        return $name;
    }

    public function getGuestCountryCode(Guest $guest): string
    {
        $cacheKey = "guest_{$guest->getId()}_country_code";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Country code (cache) for guest {$guest->getId()}");
            return $cached;
        }

        $code = $this->getCountry($guest)->getCode();
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            throw new InvalidAddressException("Invalid country code: {$code}");
        }

        $this->cache->set($cacheKey, $code, $this->ttl);
        return $code;
    }

    public function getGuestAddressInfo(Guest $guest): GuestAddressDTO
    {
        $cacheKey = "guest_{$guest->getId()}_address_info";
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof GuestAddressDTO) {
            $this->logger->debug("Address info (cache) for guest {$guest->getId()}");
            return $cached;
        }

        $address = $this->getAddress($guest);
        $city = $address->getCity();
        $country = $city->getCountry();

        $dto = new GuestAddressDTO(
            country: $country->getName(),
            countryCode: $country->getCode(),
            city: $city->getName(),
            street: $address->getStreet(),
            zipCode: $address->getZipCode(),
        );

        $this->cache->set($cacheKey, $dto, $this->ttl);
        return $dto;
    }

    // Helpers

    private function getProfileOrFail(Guest $guest)
    {
        $profile = $guest->getProfile();
        if ($profile === null) {
            throw new ProfileNotFoundException("Profile not found for guest {$guest->getId()}");
        }
        return $profile;
    }

    private function getAddress(Guest $guest): Address
    {
        $address = $this->getProfileOrFail($guest)->getAddress();
        if ($address === null) {
            throw new InvalidAddressException("Address not found for guest {$guest->getId()}");
        }
        return $address;
    }

    private function getCity(Guest $guest): City
    {
        $city = $this->getAddress($guest)->getCity();
        if ($city === null) {
            throw new InvalidAddressException("City not found for guest {$guest->getId()}");
        }
        return $city;
    }

    private function getCountry(Guest $guest): Country
    {
        $country = $this->getCity($guest)->getCountry();
        if ($country === null) {
            throw new InvalidAddressException("Country not found for guest {$guest->getId()}");
        }
        return $country;
    }
}
