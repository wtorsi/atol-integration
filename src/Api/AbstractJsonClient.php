<?php

declare(strict_types=1);

namespace Api;

use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractJsonClient extends AbstractClient
{
    protected function decode(ResponseInterface $response): array
    {
        return $response->toArray();
    }
}
