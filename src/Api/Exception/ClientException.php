<?php

declare(strict_types=1);

namespace Api\Exception;

use Symfony\Component\HttpClient\Exception\HttpExceptionTrait;

class ClientException extends \RuntimeException implements ClientExceptionInterface
{
    use HttpExceptionTrait;
}
