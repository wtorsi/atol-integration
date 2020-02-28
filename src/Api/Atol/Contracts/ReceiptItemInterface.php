<?php

namespace Api\Atol\Contracts;

use Api\Atol\Helper\ReceiptItemType;
use Api\Atol\Helper\ReceiptItemUnit;

interface ReceiptItemInterface
{
    public function getQuantity(): int;

    /**
     * Price for one position.
     */
    public function getPrice(): float;

    public function getVatType(): string;

    /**
     * @see ReceiptItemUnit
     */
    public function getUnit(): string;

    /**
     * @see ReceiptItemType
     */
    public function getPaymentType(): string;

    /**
     * @see ReceiptItemType
     */
    public function getType(): string;

    public function getReceiptName(): string;
}
