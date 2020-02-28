<?php declare(strict_types=1);

namespace Api;

interface ProviderTypeInterface
{
    const PATTERNS = [
        'visa' => '^([45]{1}[\d]{15}|[6]{1}[\d]{17})$',
        'mastercard' => '^([45]{1}[\d]{15}|[6]{1}[\d]{17})$',
        'maestro' => '^([45]{1}[\d]{15}|[6]{1}[\d]{15,17})$',
        'mir' => '^([245]{1}[\d]{15}|[6]{1}[\d]{17})$',
        'phone_russia' => '^[\+]{1}[7]{1}[9]{1}[\d]{9}$',
        'phone' => '',
        'yandexmoney' => "^41001[0-9]{7,11}$",
        'payeer' => '^[Pp]{1}[0-9]{7,15}$',
    ];

    public function isAccountValid(string $account, string $epsProviderId): bool;
}