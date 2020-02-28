<?php declare(strict_types=1);

namespace Api\Exception;

class ResponseException extends ClientClientException
{
    public static function invalidResponse(string $className, array $errors): ClientExceptionInterface
    {
        $errors = \implode('. ', $errors);

        return new self(\sprintf("API call for '%s' contains errors. %s", $className, $errors));
    }

    public static function unsupportedValue(string $className, string $key, string $value, array $response): ClientExceptionInterface
    {
        return new self(\sprintf("API call for '%s' return unsupported parameter '%s' value '%s' in response, '%s' returned", $className, $key, $value, \json_encode($response)));
    }

    public static function missingExpectedParameter(string $className, string $param, array $response): ClientExceptionInterface
    {
        return new self(\sprintf("API call for '%s' must contain '%s' parameter in response, '%s' returned", $className, $param, \json_encode($response)));
    }
}