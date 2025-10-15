<?php

namespace App\Repositories;

class UserRepository
{
    public function all(): array
    {
        // В реальном проекте — доступ к БД
        return [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test'],
            ['id' => 2, 'name' => 'Bob',   'email' => 'bob@example.test'],
        ];
    }
}