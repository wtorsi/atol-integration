<?php declare(strict_types=1);

namespace Api\Exception;

use Symfony\Component\HttpClient\Exception\HttpExceptionTrait;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ServerException extends \RuntimeException implements ServerExceptionInterface
{
    use HttpExceptionTrait;
}