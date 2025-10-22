<?php
declare(strict_types=1);

namespace App\Models;

class City
{
    private int $id;
    private string $name;
    private Country $country;

    public function __construct(int $id, string $name, Country $country)
    {
        $this->id = $id;
        $this->name = $name;
        $this->country = $country;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getCountry(): Country { return $this->country; }
}