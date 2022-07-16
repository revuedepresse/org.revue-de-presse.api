<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
