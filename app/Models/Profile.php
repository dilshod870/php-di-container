<?php
declare(strict_types=1);

namespace App\Models;

class Profile
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private Address $address;

    public function __construct(int $id, string $firstName, string $lastName, Address $address)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->address = $address;
    }

    public function getId(): int { return $this->id; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return "{$this->firstName} {$this->lastName}"; }
    public function getAddress(): Address { return $this->address; }
}
