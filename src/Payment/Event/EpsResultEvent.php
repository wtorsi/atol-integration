<?php

declare(strict_types=1);

namespace Payment\Event;

use Payment\Entity\AbstractPayment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

abstract class EpsResultEvent extends Event
{
    private AbstractPayment $payment;
    private ?Response $response = null;

    public function __construct(AbstractPayment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): EpsResultEvent
    {
        $this->response = $response;

        return $this;
    }

    public function getPayment(): ?AbstractPayment
    {
        return $this->payment;
    }
}
