<?php

use App\Routing\Router;
use App\Controllers\HomeController;

/**
 * Регистрируйте маршруты здесь.
 *
 * Поддерживаются:
 * - параметры пути: /hello/{name}
 * - параметры с ограничениями: /users/{id:\d+}
 */
return function (Router $router): void {
    // Главная
    $router->get('/', HomeController::class . '@index');

    // Параметр пути + query: /hello/Alice?age=30
    $router->get('/hello/{name}', HomeController::class . '@hello');

    // Числовой параметр с валидацией: /users/42
    $router->get('/users/{id:\d+}', HomeController::class . '@user');

    // Параметр только из query: /search?q=php
    $router->get('/search', HomeController::class . '@search');
};