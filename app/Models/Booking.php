<?php
declare(strict_types=1);

namespace App\Models;

class Booking
{
    public function __construct(
        private int $id,
        private Guest $guest,
    ) {}

    public function getId(): int { return $this->id; }
    public function getGuest(): Guest { return $this->guest; }
}
