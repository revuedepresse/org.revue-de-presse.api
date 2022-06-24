<?php

namespace App\Membership\Infrastructure\Consumer;

use App\Membership\Infrastructure\Repository\NetworkRepository;
use Psr\Log\LoggerInterface;

class NetworkConsumer
{
    public LoggerInterface $logger;

    public NetworkRepository $networkRepository;

    public function execute(): bool
    {
        try {
            // FIXME
            $members = [];
            $this->networkRepository->saveNetwork($members);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        return true;
    }
}
