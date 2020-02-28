<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ClientException;
use Api\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface as DecodingExceptionInterfaceAlias;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractClient implements ClientInterface
{
    protected HttpClientInterface $client;

    public function __construct()
    {
        $this->client = $this->buildClient();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterfaceAlias
     */
    public function request(string $uri, array $params = [], string $method = 'POST'): array
    {
        return $this->sendRequest($uri, $params, $method);
    }

    public function setProxy(?string $proxy): AbstractClient
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterfaceAlias
     */
    protected function sendRequest(?string $uri, array $params = [], string $method = 'POST'): ?array
    {
        $url = $this->buildUri($uri, $params);

        $options['headers'] = $this->buildHeaders($uri, $params, $method);

        $params = $this->buildParams($uri, $params, $method);
        $options = \array_merge($options, $this->buildPayload($uri, $params, $method));

        try {
            $response = $this->client->request($method, $url, $options);
            $data = $this->decode($response);
        } catch (TransportExceptionInterface $e) {
            throw $e;
        } catch (RedirectionExceptionInterface $e) {
            throw $e;
        } catch (ClientExceptionInterface $e) {
            throw $this->createClientApiException($e);
        } catch (ServerExceptionInterface $e) {
            throw $this->createServerException($e);
        }

        if (null === $data) {
            return null;
        }

        $this->assertResponse($data);

        return $data;
    }

    protected function buildUri(string $uri, array &$params = []): string
    {
        return $uri;
    }

    protected function buildHeaders(string $uri, array &$params = [], string $method = 'POST'): array
    {
        return [];
    }

    protected function buildParams(string $uri, array $params = [], string $method = 'POST'): array
    {
        return $params;
    }

    protected function buildPayload(string $uri, array $params = [], string $method = 'POST'): array
    {
        switch (\strtoupper($method)) {
            case 'GET':
                $options = ['query' => $params];
                break;
            case 'POST':
                $options = ['body' => $params];
                break;
            default:
                $options = [];
        }

        return $options;
    }

    /**
     * @throws ClientExceptionInterface Should be thrown if smth is incorrect
     */
    protected function assertResponse(array $response): void
    {
    }

    /**
     * @throws TransportExceptionInterface     When the body cannot be decoded or when a network error occurs
     * @throws RedirectionExceptionInterface   On a 3xx when $throw is true and the "max_redirects" option has been reached
     * @throws ClientExceptionInterface        On a 4xx when $throw is true
     * @throws ServerExceptionInterface        On a 5xx when $throw is true
     * @throws DecodingExceptionInterfaceAlias
     */
    abstract protected function decode(ResponseInterface $response): array;

    abstract protected function buildClient(): HttpClientInterface;

    protected function createClientApiException(ClientExceptionInterface $e): ClientExceptionInterface
    {
        return new ClientException($e->getResponse());
    }

    protected function createServerException(ServerExceptionInterface $e): ServerExceptionInterface
    {
        return new ServerException($e->getResponse());
    }
}
