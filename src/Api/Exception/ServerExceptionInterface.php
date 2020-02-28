<?php

namespace Api\Exception;

use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface as BaseException;

interface ServerExceptionInterface extends ExceptionInterface, BaseException
{
}