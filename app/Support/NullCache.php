<?php
declare(strict_types=1);

namespace App\Support;

class NullCache implements CacheInterface
{
    public function get(string $key): mixed { return null; }
    public function set(string $key, mixed $value, int $ttl): void {}
}
