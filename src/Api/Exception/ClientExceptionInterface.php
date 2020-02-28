<?php declare(strict_types=1);

namespace Api\Exception;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface as BaseException;

interface ClientExceptionInterface extends ExceptionInterface, BaseException
{
}