<?php
declare(strict_types=1);

namespace App\Support;

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
}
