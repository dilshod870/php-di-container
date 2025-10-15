<?php

namespace App\Routing;

use App\Container\Container;
use App\Http\Request;
use Exception;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

class Router
{
    /**
     * @var array<int, array{method:string, pattern:string, regex:string, variables:array<int,string>, handler:mixed}>
     */
    private array $routes = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $pattern, mixed $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, mixed $handler): void
    {
        [$regex, $variables] = $this->compilePattern($pattern);
        $this->routes[] = [
            'method'    => strtoupper($method),
            'pattern'   => $pattern,
            'regex'     => $regex,
            'variables' => $variables,
            'handler'   => $handler,
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function compilePattern(string $pattern): array
    {
        // Поддержка: /hello/{name} и /users/{id:\d+}
        $variables = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/', function ($m) use (&$variables) {
            $name = $m[1];
            $variables[] = $name;
            $constraint = $m[2] ?? '[^/]+';
            return '(?P<' . $name . '>' . $constraint . ')';
        }, $pattern);

        $regex = '#^' . $regex . '$#';
        return [$regex, $variables];
    }

    public function dispatch(Request $request): mixed
    {
        $path = $request->path();
        $method = $request->method();

        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            // Запоминаем допустимые методы для 405
            $allowed[] = $route['method'];

            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            foreach ($route['variables'] as $var) {
                if (isset($matches[$var])) {
                    $params[$var] = $matches[$var];
                }
            }

            $request->setRouteParams($params);
            return $this->invoke($route['handler'], $request, $params);
        }

        if (!empty($allowed)) {
            throw new HttpException('Method Not Allowed', 405, ['Allow' => implode(', ', array_unique($allowed))]);
        }

        throw new HttpException('Not Found', 404);
    }

    private function invoke(mixed $handler, Request $request, array $routeParams): mixed
    {
        // Форматы:
        // - 'App\Controllers\HomeController@index'
        // - [App\Controllers\HomeController::class, 'index']
        // - [new HomeController(...), 'index']
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = $this->container->make($class);
            $ref = new ReflectionMethod($controller, $method);
            $args = $this->buildArguments($ref, $request, $routeParams);
            return $ref->invokeArgs($controller, $args);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$target, $method] = $handler;
            if (is_string($target)) {
                $controller = $this->container->make($target);
            } else {
                $controller = $target;
            }
            $ref = new ReflectionMethod($controller, $method);
            $args = $this->buildArguments($ref, $request, $routeParams);
            return $ref->invokeArgs($controller, $args);
        }

        if (is_callable($handler)) {
            // callable (Closure) — резолвим аргументы
            $ref = $this->reflectCallable($handler);
            $args = $this->buildArguments($ref, $request, $routeParams);
            return $handler(...$args);
        }

        throw new Exception('Invalid route handler');
    }

    private function reflectCallable(callable $callable): ReflectionFunctionAbstract
    {
        // Поддержка Closure и статических методов вида 'Class::method'
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }
        if (is_string($callable) && str_contains($callable, '::')) {
            [$class, $method] = explode('::', $callable, 2);
            return new ReflectionMethod($class, $method);
        }
        // Для Closure PHP автоматически создаст ReflectionFunction через замыкание
        return new \ReflectionFunction($callable(...));
    }

    /**
     * Собирает список аргументов для вызова метода/функции.
     * Правила:
     * - class-typed: если App\Http\Request — подставить текущий Request;
     *   иначе — получить из контейнера $container->make(тип)
     * - scalar: сначала из $routeParams по имени, затем из query/body по имени,
     *   затем значение по умолчанию, иначе 400.
     */
    private function buildArguments(ReflectionFunctionAbstract $ref, Request $request, array $routeParams): array
    {
        $args = [];
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            $name = $param->getName();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                if (is_a($className, Request::class, true)) {
                    $args[] = $request;
                    continue;
                }
                $args[] = $this->container->make($className);
                continue;
            }

            // scalar: route -> query -> body -> default
            if (array_key_exists($name, $routeParams)) {
                $args[] = $this->castScalar($routeParams[$name], $type);
            } elseif (null !== ($v = $request->query($name, null))) {
                $args[] = $this->castScalar($v, $type);
            } elseif (null !== ($v = $request->body($name, null))) {
                $args[] = $this->castScalar($v, $type);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new HttpException("Bad Request: missing parameter \${$name}", 400);
            }
        }
        return $args;
    }

    private function castScalar(mixed $value, ?\ReflectionType $type): mixed
    {
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin() === false) {
            return $value;
        }

        $t = $type->getName();
        if ($t === 'int') {
            return (int) $value;
        }
        if ($t === 'float') {
            return (float) $value;
        }
        if ($t === 'bool') {
            if (is_string($value)) {
                $lv = strtolower($value);
                if (in_array($lv, ['1', 'true', 'yes', 'on'], true)) return true;
                if (in_array($lv, ['0', 'false', 'no', 'off'], true)) return false;
            }
            return (bool) $value;
        }
        if ($t === 'string') {
            return (string) $value;
        }
        return $value;
    }
}

class HttpException extends Exception
{
    public function __construct(
        public $message = '',
        public int $status = 500,
        public array $headers = []
    ) {
        parent::__construct($message, $status);
    }
}