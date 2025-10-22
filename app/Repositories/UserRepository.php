<?php

namespace App\Repositories;

use App\Container\Exceptions\NotFoundException;

class UserRepository
{
    /**
     * @var array|array[]
     */
    private array $data;

    public function __construct()
    {
        $this->data = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.test'],
            ['id' => 2, 'name' => 'Bob',   'email' => 'bob@example.test'],
        ];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function getById(int $id): array
    {
        foreach ($this->data as $user) {
            if ((int)$user['id'] === $id) {
                return $user;
            }
        }
        return [];
    }

    /**
     * @throws \App\Container\Exceptions\NotFoundException
     */
    public function getFindOrFail(string $id): array
    {
        $result = $this->getById($id);
        if (!$result) {
            throw new NotFoundException("User with id {$id} not found");
        }

        return $result;
    }
}