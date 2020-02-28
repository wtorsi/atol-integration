<?php

namespace Api\Atol\Exception;

use Api\Exception\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MisconfigurationException extends ClientException
{
    private ?array $error;

    public function __construct(ResponseInterface $response, ?array $error = null)
    {
        $this->error = $error;
        parent::__construct($response);
    }
}