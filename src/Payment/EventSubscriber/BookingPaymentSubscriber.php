<?php

namespace Payment\EventSubscriber;

use Payment\Entity\BookingPayment;
use Payment\Event\PaymentCompletedEvent;
use Payment\Messenger\Message\CreateReceiptMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BookingPaymentSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public static function getSubscribedEvents()
    {
        return [
            PaymentCompletedEvent::class => 'onPaymentComplete',
        ];
    }

    public function onPaymentComplete(PaymentCompletedEvent $event): void
    {
        if (!$event->getPayment() instanceof BookingPayment) {
            return;
        }

        $this->bus->dispatch(CreateReceiptMessage::factory($event->getPayment()));
    }
}