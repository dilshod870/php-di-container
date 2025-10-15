<?php

namespace App\Http;

class Request
{
    private string $method;
    private string $path;
    private array $query = [];
    private array $body = [];
    private array $headers = [];

    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $headers = [],
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
    }

    private array $routeParams = [];

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $query = $_GET ?? [];

        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        }

        $raw = file_get_contents('php://input') ?: '';
        $contentType = strtolower($headers['Content-Type'] ?? $headers['content-type'] ?? '');
        $body = $_POST ?: [];

        if ($raw !== '') {
            if (str_contains($contentType, 'application/json')) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $body = array_merge($body, $json);
                }
            } else {
                // Для form-urlencoded без $_POST
                parse_str($raw, $parsed);
                if (is_array($parsed)) {
                    $body = array_merge($body, $parsed);
                }
            }
        }

        return new self($method, $path, $query, $body, $headers);
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === strtolower($key)) {
                return $v;
            }
        }
        return $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $data = array_merge($this->query, $this->body);
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->routeParams, $this->query, $this->body);
    }
}