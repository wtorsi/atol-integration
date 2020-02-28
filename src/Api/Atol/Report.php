<?php declare(strict_types=1);

namespace Api\Atol;

class Report
{
    private string $status;
    private array $error = [];

    public function __construct(array $response)
    {
        $this->status = $response['status'];
        $this->error = $response['error'] ?? [];
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getError(): array
    {
        return $this->error;
    }
}