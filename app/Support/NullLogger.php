<?php
declare(strict_types=1);

namespace App\Support;

class NullLogger implements LoggerInterface
{
    public function debug(string $message): void {}
    public function error(string $message): void {}
}
