<?php

namespace Payment\Messenger\Handler;

use Api\Atol\Atol;
use Api\Atol\Token;
use Doctrine\ORM\EntityManagerInterface;
use Payment\Messenger\Message\ApproveReceiptMessage;
use Payment\Messenger\Message\CreateReceiptMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

class CreateReceiptHandler extends AbstractReceiptMessageHandler
{
    private MessageBusInterface $bus;

    public function __construct(EntityManagerInterface $em, MessageBusInterface $bus, Atol $provider)
    {
        $this->bus = $bus;
        parent::__construct($em, $provider);
    }

    public function __invoke(CreateReceiptMessage $message)
    {
        $this->wrap(function (Token $token) use ($message): void {
            $entity = $this->getEntity($message);
            $id = $this->provider->sell($token, $entity);

            $entity->markRequested($id);
            $this->em->persist($entity);
            $this->em->flush();

            //send to approve
            $this->bus->dispatch(new Envelope(ApproveReceiptMessage::factory($entity), [
                new DelayStamp(5000),
                new DispatchAfterCurrentBusStamp(),
            ]));
        });
    }
}
