<?php
declare(strict_types=1);

namespace App\Models;

class Address
{
    private int $id;
    private string $street;
    private string $zipCode;
    private City $city;

    public function __construct(int $id, string $street, string $zipCode, City $city)
    {
        $this->id = $id;
        $this->street = $street;
        $this->zipCode = $zipCode;
        $this->city = $city;
    }

    public function getId(): int { return $this->id; }
    public function getStreet(): string { return $this->street; }
    public function getZipCode(): string { return $this->zipCode; }
    public function getCity(): City { return $this->city; }
}
