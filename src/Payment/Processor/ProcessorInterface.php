<?php

declare(strict_types=1);

namespace Payment\Processor;

use Payment\Entity\AbstractPayment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ProcessorInterface
{
    public function check(Request $request): Response;

    public function process(Request $request): Response;

    public function fetchPaymentFromRequest(Request $request): ?AbstractPayment;
}
