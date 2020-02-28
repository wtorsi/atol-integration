<?php declare(strict_types=1);

namespace Payment\Messenger\Handler;

use Api\Atol\Atol;
use Api\Atol\Contracts\ReceiptInterface;
use Api\Atol\Exception\MisconfigurationException;
use Api\Atol\Exception\TokenExpiredException;
use Doctrine\ORM\EntityManagerInterface;
use Payment\Messenger\Message\ReceiptMessageInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

abstract class AbstractReceiptMessageHandler implements MessageHandlerInterface
{
    protected Atol $provider;
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, Atol $provider)
    {
        $this->provider = $provider;
        $this->em = $em;
    }

    protected function wrap(\Closure $param): void
    {
        try {
            $token = $this->provider->loadToken();
            $param->call($this, $token);
        } catch (TokenExpiredException $e) {
            $this->provider->unsetToken();
            throw $e;
        } catch (MisconfigurationException $e) {
            //configuration is not correct, prevent retry
            throw new UnrecoverableMessageHandlingException(\sprintf('Configuration is not correct.'), $e->getCode(), $e);
        }
    }

    protected function getEntity(ReceiptMessageInterface $message): ReceiptInterface
    {
        return $this->em->find($message->getClassName(), $message->getId());
    }
}