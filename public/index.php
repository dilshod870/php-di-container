<?php

use App\Http\Request;
use App\Routing\HttpException;
use App\Routing\Router;

/** @var \App\Container\Container $container */
$container = require __DIR__ . '/../bootstrap/app.php';

require __DIR__ . '/../bootstrap/autoload.php';

// Создаём Request из PHP-глобалок
$request = Request::fromGlobals();

// Создаём роутер и регистрируем маршруты
$router = new Router($container);

/** @var callable $routes */
$routes = require __DIR__ . '/../config/routes.php';
$routes($router);
//print_r($router);
//exit;
// Диспетчеризация
try {
    $result = $router->dispatch($request);
    // Ответы: массив => JSON, строка => text/plain, иначе JSON по умолчанию
    if ($result instanceof \Stringable) {
        header('Content-Type: text/plain; charset=utf-8');
        echo (string) $result;
        exit;
    }

    if (is_string($result)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $result;
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
} catch (HttpException $e) {
    http_response_code($e->status);
    foreach ($e->headers as $k => $v) {
        header($k . ': ' . $v);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => $e->message,
        'status' => $e->status,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
}