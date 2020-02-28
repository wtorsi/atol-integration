<?php

namespace Api\Atol\Helper;

use Dev\Enum\AbstractEnum;

final class SnoType extends AbstractEnum
{
    public const OSN = 'osn';
    public const USN_INCOME = 'usn_income';
    public const USN_INCOME_OUTCOME = 'usn_income_outcome';
    public const ENVD = 'envd';
    public const ESN = 'esn';
    public const PATENT = 'patent';
}