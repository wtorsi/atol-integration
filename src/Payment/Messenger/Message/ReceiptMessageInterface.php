<?php declare(strict_types=1);

namespace Payment\Messenger\Message;

interface ReceiptMessageInterface
{
    public function getId(): string;

    public function getClassName(): string;
}