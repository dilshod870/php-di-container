<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function listUsers(): array
    {
        return $this->users->all();
    }

    public function getUser(int $id): array
    {
        return $this->users->getFindOrFail($id);
    }
}