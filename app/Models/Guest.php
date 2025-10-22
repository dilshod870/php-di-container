<?php
declare(strict_types=1);

namespace App\Models;

class Guest
{
    private int $id;
    private string $email;
    private Profile $profile;

    public function __construct(int $id, string $email, Profile $profile)
    {
        $this->id = $id;
        $this->email = $email;
        $this->profile = $profile;
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getProfile(): Profile { return $this->profile; }
    public function getFullName(): string { return $this->profile->getFullName(); }
}
