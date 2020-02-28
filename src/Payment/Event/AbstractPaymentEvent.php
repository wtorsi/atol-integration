<?php declare(strict_types=1);

namespace Payment\Event;

use Payment\Entity\AbstractPayment;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractPaymentEvent extends Event
{
    /**
     * @var AbstractPayment
     */
    private AbstractPayment $payment;

    public function __construct(AbstractPayment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return AbstractPayment
     */
    public function getPayment(): ?AbstractPayment
    {
        return $this->payment;
    }
}