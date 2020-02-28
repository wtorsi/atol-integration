<?php declare(strict_types=1);

namespace Payment\Messenger\Message;

use Api\Atol\Contracts\ReceiptInterface;

abstract class AbstractReceiptMessage implements ReceiptMessageInterface
{
    private string $id;
    private string $className;

    public function __construct(string $id, string $className)
    {
        $this->id = $id;
        $this->className = $className;
    }

    public static function factory(ReceiptInterface $receipt): self
    {
        return new static((string) $receipt->getId(), \get_class($receipt));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}