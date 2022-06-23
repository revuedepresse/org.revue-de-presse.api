<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Correlation;

use Symfony\Component\Console\Input\InputAwareInterface;

interface CorrelationIdAwareCommandInterface
{
    public const OPTION_CORRELATION_ID = 'correlation_id';

    public function convertInputOptionIntoCorrelationId(InputAwareInterface $input): CorrelationIdInterface;
}