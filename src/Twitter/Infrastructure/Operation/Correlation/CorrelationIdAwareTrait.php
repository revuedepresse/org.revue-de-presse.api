<?php

namespace App\Twitter\Infrastructure\Operation\Correlation;

trait CorrelationIdAwareTrait
{
    private CorrelationIdInterface $correlationId;

    public function correlationId(): CorrelationIdInterface {
        return $this->correlationId;
    }
}