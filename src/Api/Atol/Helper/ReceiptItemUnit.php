<?php

namespace Api\Atol\Helper;

use Dev\Enum\AbstractEnum;

final class ReceiptItemUnit extends AbstractEnum
{
    public const QUANTITY = 'quantity';

    public static function label(string $type): string
    {
        return 'unit.'.$type;
    }
}