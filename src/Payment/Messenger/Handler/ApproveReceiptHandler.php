<?php

declare(strict_types=1);

namespace Payment\Messenger\Handler;

use Api\Atol\Helper\ReportStatus;
use Api\Atol\Token;
use Payment\Messenger\Message\ApproveReceiptMessage;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class ApproveReceiptHandler extends AbstractReceiptMessageHandler
{
    public function __invoke(ApproveReceiptMessage $message)
    {
        $this->wrap(function (Token $token) use ($message): void {
            $entity = $this->getEntity($message);
            $report = $this->provider->report($token, $entity->getReceiptId());

            if (ReportStatus::WAIT === $report->getStatus()) {
                throw new \Exception('Receipt has pending status. Retry.');
            }

            if (ReportStatus::DONE !== $report->getStatus()) {
                $code = $report->getError()['code'];
                $text = $report->getError()['text'];
                throw new UnrecoverableMessageHandlingException(\sprintf('Receipt was failed with error %s ', $text));
            }

            $entity->markApproved();
            $this->em->persist($entity);
            $this->em->flush();
        });
    }
}
