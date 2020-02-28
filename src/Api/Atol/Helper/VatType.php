<?php

namespace Api\Atol\Helper;

use Dev\Enum\AbstractEnum;

final class VatType extends AbstractEnum
{
    public const NONE = 'none';
    public const VAT0 = 'vat0';
    public const VAT18 = 'vat18';
    private const VATS = [
        self::NONE => 0,
        self::VAT0 => 0,
        self::VAT18 => .18,
    ];

    public static function calculate(string $type, float $price): float
    {
        $vat = self::VATS[$type];

        return \round($vat * $price, 2);
    }
}