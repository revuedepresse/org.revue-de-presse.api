<?php

namespace App\Twitter\Infrastructure\Operation\Correlation;

interface CorrelationIdAwareInterface
{
    public function correlationId(): CorrelationIdInterface;
}