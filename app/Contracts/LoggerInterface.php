<?php

    namespace App\Contracts;

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;
}