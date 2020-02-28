<?php

namespace Api\Atol\Contracts;

interface ClientInterface
{
    public function getName(): string;

    public function getEmail(): string;

    public function getPhone(): string;

}