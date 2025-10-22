<?php

namespace App\Container;

use App\Container\Exceptions\ContainerException;
use App\Container\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;

class Container
{
    /**
     * @var array<string, array{concrete: callable|string, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Зарегистрировать абстракцию -> конкретную реализацию.
     *
     * $concrete может быть:
     * - строка (имя класса)
     * - замыкание (callable(Container $c): object)
     * - null (тогда concrete = abstract, для автосвязывания)
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => $shared,
        ];
    }

    /**
     * Зарегистрировать singleton.
     */
    public function singleton(string $abstract,  $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Зарегистрировать уже готовый инстанс.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Проверить наличие регистрации/инстанса.
     */
    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]) || class_exists($abstract);
    }

    /**
     * Получить (создать) инстанс.
     *
     * @param array<string, mixed> $parameters Переопределение скалярных параметров конструктора по имени
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // Уже созданная singleton/instance
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        $concrete = $abstract;
        $shared = false;

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
            $shared   = $this->bindings[$abstract]['shared'];
        }

        $object = $this->resolve($concrete, $parameters);

        if ($shared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Вызвать callable с автоподстановкой зависимостей по типам.
     *
     * @param callable $callable
     * @param array<string, mixed> $parameters скалярные параметры по имени
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        $ref = new ReflectionFunction($callable(...));
        $args = [];

        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
                continue;
            }

            $name = $param->getName();
            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new ContainerException("Unable to resolve parameter \${$name} for callable");
            }
        }

        return $callable(...$args);
    }

    /**
     * @param callable|string $concrete
     * @param array<string, mixed> $parameters
     */
    protected function resolve(callable|string $concrete, array $parameters = []): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (!is_string($concrete)) {
            throw new ContainerException('Invalid binding type');
        }

        return $this->build($concrete, $parameters);
    }

    /**
     * Построить объект по имени класса с автосвязыванием зависимостей.
     *
     * @param string $class
     * @param array<string, mixed> $parameters
     * @return mixed
     * @throws \App\Container\Exceptions\ContainerException
     * @throws \App\Container\Exceptions\NotFoundException
     * @throws \ReflectionException
     */
    protected function build(string $class, array $parameters = []): mixed
    {
        if (!class_exists($class)) {
            throw new NotFoundException("Class {$class} does not exist");
        }

        $ref = new ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new ContainerException("Class {$class} is not instantiable");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return new $class();
        }

        $deps = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            // Классовые зависимости — через контейнер
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $deps[] = $this->make($type->getName());
                continue;
            }

            // Скалярные — из параметров по имени, либо значение по умолчанию
            $name = $param->getName();

            if (array_key_exists($name, $parameters)) {
                $deps[] = $parameters[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
            } else {
                throw new ContainerException("Unable to resolve scalar parameter \${$name} for class {$class}");
            }
        }

        return $ref->newInstanceArgs($deps);
    }
}