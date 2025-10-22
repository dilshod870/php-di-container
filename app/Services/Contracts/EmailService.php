<?php
declare(strict_types=1);

namespace App\Services\Contracts;

interface EmailService
{
    public function send(string $to, string $subject, string $message): void;
}
