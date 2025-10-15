<?php

require __DIR__ . '/autoload.php';

use App\Container\Container;

/**
 * Создаём контейнер и применяем биндинги из конфига
 */
$container = new Container();

/** @var callable $bootstrap */
$bootstrap = require __DIR__ . '/../config/container.php';
$bootstrap($container);

return $container;