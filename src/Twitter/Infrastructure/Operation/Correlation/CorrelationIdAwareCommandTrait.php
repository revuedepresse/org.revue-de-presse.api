<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Correlation;

use Symfony\Component\Console\Input\InputInterface;

trait CorrelationIdAwareCommandTrait
{
    public static function convertInputOptionIntoCorrelationId(InputInterface $input): CorrelationIdInterface {
        $correlationId = CorrelationId::generate();

        if ($input->hasOption(CorrelationIdAwareCommandInterface::OPTION_CORRELATION_ID)) {
            $correlationId = CorrelationId::fromString($input->getOption(CorrelationIdAwareCommandInterface::OPTION_CORRELATION_ID));
        }

        return $correlationId;
    }
}