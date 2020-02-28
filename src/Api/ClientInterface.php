<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

interface ClientInterface
{
    /**
     * @param string $uri
     * @param array  $params
     * @param string $method
     *
     * @return array
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     */
    public function request(string $uri, array $params = [], string $method = 'POST'): ?array;
}
