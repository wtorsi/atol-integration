<?php

namespace Api\Atol;

class Token
{
    private string $token;
    private \DateTimeImmutable $expirationDatetime;

    public function __construct(string $token, \DateTimeImmutable $expirationDatetime)
    {
        $this->token = $token;
        $this->expirationDatetime = $expirationDatetime;
    }

    public function __toString(): string
    {
        return $this->token;
    }

    public function isExpired(): bool
    {
        return $this->expirationDatetime < new \DateTimeImmutable();
    }
}