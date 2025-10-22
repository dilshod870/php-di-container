<?php

namespace App\Models;

class City {
    private $name;
    private $country;

    public function __construct(string $name, string $country) {
        $this->name = $name;
        $this->country = $country;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getCountry(): string {
        return $this->country;
    }
}