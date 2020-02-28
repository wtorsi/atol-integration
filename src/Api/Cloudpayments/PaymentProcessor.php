<?php

namespace Api\Cloudpayments;

use Currency\Currency;
use Doctrine\ORM\EntityManagerInterface;
use Exception\NotFoundHttpException;
use Payment\Entity\AbstractPayment;
use Payment\Eps\EpsTransaction;
use Payment\Exception\ProcessorException;
use Payment\Processor\AbstractProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PaymentProcessor extends AbstractProcessor
{
    private array $credentials = [
        'ips' => ['130.193.70.192', '185.98.85.109'],
        'private_key' => '',
    ];

    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $dispatcher, LoggerInterface $logger, array $credentials = [])
    {
        $this->credentials = \array_replace($this->credentials, $credentials);
        parent::__construct($em, $dispatcher, $logger);
    }

    public function createErrorResponse(Request $request, \Throwable $e): Response
    {
        switch (true) {
            case $e instanceof NotFoundHttpException:
                $code = 10;
                break;
            default:
                $code = 13;
        }

        return new JsonResponse(['code' => $code]);
    }

    public function createSuccessResponse(Request $request, AbstractPayment $payment): Response
    {
        return new JsonResponse(['code' => 0]);
    }

    protected function assertRequest(Request $request): void
    {
        if (!\in_array($request->getClientIp(), $this->credentials['ips'])) {
            throw ProcessorException::createClientIpException($request->getClientIp());
        }

        if (!$request->headers->has($hashKey = 'Content-HMAC')) {
            throw ProcessorException::createMissedParamsException([$hashKey]);
        }

        $hash = \hash_hmac('sha256', $request->getContent(), $this->credentials['private_key'], true);
        $hash = \base64_encode($hash);

        if ($hash !== $request->headers->get($hashKey)) {
            throw ProcessorException::createSignException($hash, $request->headers->get($hashKey));
        }

        $post = $request->request;
        $required = [
            'TransactionId',
            'Amount',
            'Currency',
            'OperationType',
            'InvoiceId',
            'Status',
        ];

        $diff = \array_diff_key(\array_flip($required), $post->all());
        if (\count($diff) > 0) {
            throw ProcessorException::createMissedParamsException(\array_keys($diff));
        }

        if (!\in_array($post->get('Status'), ['Completed'])) {
            throw ProcessorException::createStatusException();
        }

        if ('Payment' !== $post->get('OperationType')) {
            throw new ProcessorException(\sprintf('Not valid operation type.'));
        }

        if ((float) $post->filter('Amount', null, FILTER_VALIDATE_FLOAT) <= 0) {
            throw new ProcessorException(\sprintf('Amount is less or equals zero.'));
        }

        if (!Currency::has($post->get('Currency'))) {
            throw ProcessorException::createCurrencyException($post->get('Currency'), Currency::choices());
        }
    }

    protected function parsePaymentIdFromRequest(Request $request): ?string
    {
        return $request->request->get('InvoiceId');
    }

    protected function createEpsTransaction(Request $request, AbstractPayment $payment): EpsTransaction
    {
        return new EpsTransaction(
            (string) $request->request->get('TransactionId'),
            (float) $request->request->get('Amount'),
            (string) $request->request->get('Currency')
        );
    }
}