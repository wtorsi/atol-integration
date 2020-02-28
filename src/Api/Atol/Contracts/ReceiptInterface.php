<?php

namespace Api\Atol\Contracts;

use Ramsey\Uuid\UuidInterface;

interface ReceiptInterface
{
    public function getId(): UuidInterface;

    public function getReceiptId(): ?UuidInterface;

    public function getClient(): ClientInterface;

    /**
     * @return ReceiptItemInterface[]
     */
    public function getReceiptItems(): array;

    public function getPaidAmount(): float;

    public function markRequested(UuidInterface $id): void;

    public function markApproved(): void;
}