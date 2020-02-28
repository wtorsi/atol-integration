<?php

namespace Payment\Controller;

use Payment\Event\EpsFailureResultEvent;
use Payment\Event\EpsSuccessResultEvent;
use Payment\Processor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/payment/callback")
 */
class CallbackController extends \AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/check", methods={"GET", "POST"}, name="payment_callback_check")
     *
     * @throws \Throwable
     */
    public function check(Request $request, ProcessorInterface $epsProcessor): Response
    {
        return $epsProcessor->check($request);
    }

    /**
     * @Route("/process", methods={"GET", "POST"}, name="payment_callback_process")
     *
     * @throws \Throwable
     */
    public function process(Request $request, ProcessorInterface $epsProcessor): Response
    {
        return $epsProcessor->process($request);
    }
}
