<?php

use App\Container\Container;
use App\Contracts\LoggerInterface;
use App\Services\FileLogger;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Controllers\HomeController;

return function (Container $container): void {
    // Пример singleton: один логгер на всё приложение
    $container->singleton(LoggerInterface::class, function (Container $c) {
        $logPath = __DIR__ . '/../storage/app.log';
        return new FileLogger($logPath);
    });

    // Классы можно не указывать явно, автосвязывание создаст их автоматически:
    $container->bind(UserRepository::class);
    $container->bind(UserService::class);
    $container->bind(HomeController::class);
};