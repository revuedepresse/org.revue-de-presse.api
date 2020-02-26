<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Log\StatusLoggerInterface;

trait StatusLoggerTrait
{
    protected StatusLoggerInterface $collectStatusLogger;

    public function setStatusLogger(StatusLoggerInterface $statusLogger): self
    {
        $this->collectStatusLogger = $statusLogger;

        return $this;
    }
}