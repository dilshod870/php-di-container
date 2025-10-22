<?php
namespace App\Models;

class Country
{
    private int $id;
    private string $name;
    private string $code;

    public function __construct(int $id, string $name, string $code)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = strtoupper($code);
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
}