<?php

namespace Api\Atol;

use Api\AbstractJsonClient;
use Api\Atol\Exception\AccessDeniedException;
use Api\Atol\Exception\MisconfigurationException;
use Api\Atol\Exception\MissingTokenException;
use Api\Atol\Exception\TokenExpiredException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client extends AbstractJsonClient
{
    private const BASE_PATH = '/possystem/v4';
    private string $url;

    public function __construct(bool $isTest = false)
    {
        $this->url = $isTest
            ? 'https://testonline.atol.ru'
            : 'https://online.atol.ru';

        parent::__construct();
    }

    protected function buildUri(string $uri, array &$params = []): string
    {
        $group = '';
        if (isset($params['group'])) {
            $group = '/'.$params['group'];
            unset($params['group']);
        }

        return self::BASE_PATH.$group.$uri;
    }

    protected function buildPayload(string $uri, array $params = [], string $method = 'POST'): array
    {
        switch (\strtoupper($method)) {
            case 'GET':
                $options = ['query' => $params];
                break;
            case 'POST':
                $options = ['json' => $params];
                break;
            default:
                $options = [];
        }

        return $options;
    }

    protected function createClientApiException(ClientExceptionInterface $e): ClientExceptionInterface
    {
        $response = $e->getResponse();
        try {
            $decoded = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw $e;
        }

        if (!isset($decoded['error']) || !$decoded['error']) {
            return parent::createClientApiException($e);
        }

        $code = (int) ($decoded['error']['code'] ?? 0);
        switch ($code) {
            case 10:
                return new MissingTokenException($response);
            case 11:
                return new TokenExpiredException($response);
            case 12:
                return new AccessDeniedException($response, $decoded['error']);
            case 20:
            case 21:
            case 32:
                return new MisconfigurationException($response, $decoded['error']);
            default:
                return parent::createClientApiException($e);
        }
    }

    protected function buildHeaders(string $uri, array &$params = [], string $method = 'POST'): array
    {
        $headers = [];
        if (isset($params['token'])) {
            $headers['Token'] = (string) $params['token'];
            unset($params['token']);
        }

        return $headers;
    }

    protected function buildClient(): HttpClientInterface
    {
        return HttpClient::create([
            'base_uri' => $this->url,
            'timeout' => 10.0,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
        ]);
    }
}