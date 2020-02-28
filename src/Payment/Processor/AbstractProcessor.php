<?php

declare(strict_types=1);

namespace Payment\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Exception\NotFoundHttpException;
use Payment\Entity\AbstractPayment;
use Payment\Event\PaymentCompletedEvent;
use Payment\Exception\ProcessorException;
use Payment\Helper\PaymentStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractProcessor implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    public function check(Request $request): Response
    {
        return $this->doAction($request);
    }

    public function process(Request $request): Response
    {
        $that = $this;

        return $this->doAction($request, function (Request $request, AbstractPayment $payment) use ($that): void {
            $that->complete($request, $payment);
        });
    }

    public function fetchPaymentFromRequest(Request $request): ?AbstractPayment
    {
        /** @var string $id */
        if (!$id = $this->parsePaymentIdFromRequest($request)) {
            return null;
        }

        $er = $this->em->getRepository(AbstractPayment::class);

        return $er->find($id);
    }

    abstract protected function parsePaymentIdFromRequest(Request $request): ?string;

    abstract protected function createErrorResponse(Request $request, \Throwable $e): Response;

    abstract protected function createSuccessResponse(Request $request, AbstractPayment $payment): Response;

    abstract protected function assertRequest(Request $request): void;

    private function getPaymentToProcess(Request $request): AbstractPayment
    {
        $payment = $this->fetchPaymentFromRequest($request);

        if (null === $payment) {
            throw new NotFoundHttpException();
        }

        if (PaymentStatus::CREATED !== $payment->getStatus()) {
            throw ProcessorException::createNotValidToProcessException();
        }

        return $payment;
    }

    private function complete(Request $request, AbstractPayment $payment): void
    {
        $epsTransaction = $this->createEpsTransaction($request, $payment);
        $this->logger->info('Start processing payment with transaction.', [
            'payment' => $payment,
            'transaction' => $epsTransaction,
        ]);

        $this->em->beginTransaction();
        try {
            $payment->complete($epsTransaction);
            $this->em->persist($payment);
            $this->em->flush();
            // main event to add money to bill
            $this->dispatcher->dispatch(new PaymentCompletedEvent($payment));
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function logException(Request $request, \Throwable $e): void
    {
        switch (true) {
            case $e instanceof NotFoundHttpException:
                $this->logger->error($error = \sprintf('Payment was not found.'), [
                    'request' => $request,
                ]);
                break;
            case $e instanceof ProcessorException:
                $this->logger->error($error = 'Request is not valid. '.$e->getMessage(), [
                    'request' => $request,
                ]);

                break;
            default:
                $this->logger->critical($error = 'Error occurred while processing payment.', [
                    'exception' => $e,
                    'request' => $request,
                ]);

                break;
        }
    }

    private function doAction(Request $request, ?\Closure $closure = null): Response
    {
        try {
            $this->assertRequest($request);
            $payment = $this->getPaymentToProcess($request);

            if (null !== $closure) {
                \call_user_func($closure, $request, $payment);
            }
        } catch (\Throwable $e) {
            $this->logException($request, $e);

            return $this->createErrorResponse($request, $e);
        }

        return $this->createSuccessResponse($request, $payment);
    }
}
