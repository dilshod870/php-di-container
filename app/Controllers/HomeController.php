<?php

namespace App\Controllers;

use App\Contracts\LoggerInterface;
use App\Http\Request;
use App\Services\UserService;

class HomeController
{
    private UserService $users;
    private LoggerInterface $logger;

    public function __construct(
        UserService $users,
        LoggerInterface $logger
    ) {
        $this->users = $users;
        $this->logger = $logger;
    }

    // Был раньше: базовый экшен
    public function index(): array
    {
        $data = [
            'message' => 'Hello from HomeController@index',
            'users'   => $this->users->listUsers(),
        ];

        $this->logger->info('HomeController@index served', ['users_count' => count($data['users'])]);

        return $data;
    }

    // Пример: параметр пути + параметр из query
    // GET /hello/{name}?age=30
    public function hello(string $name, Request $request, int $age = 0): array
    {
        $this->logger->info('hello called', ['name' => $name, 'age' => $age]);

        return [
            'hello' => $name,
            'age'   => $age, // возьмётся из query по имени аргумента: ?age=30
            'query' => $request->query(), // все query-параметры
        ];
    }

    // Пример: числовой параметр пути
    // GET /users/{id:\d+}
    public function user(int $id): array
    {

        $user = $this->users->getUser($id);

        if (!$user) {
            return ['error' => 'User not found', 'id' => $id];
        }

        return ['user' => $user];
    }

    // Пример: параметр только из query
    // GET /search?q=php
    public function search(Request $request, string $q): array
    {
        // В реале — поиск в БД; здесь просто демонстрация
        $this->logger->info('search', ['q' => $q]);

        return [
            'q' => $q,
            'filters' => $request->query(),
            'results' => [
                ['id' => 1, 'title' => "Result for {$q} #1"],
                ['id' => 2, 'title' => "Result for {$q} #2"],
            ],
        ];
    }
}