<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Logging;

use Monolog\LogRecord;

final class TokenRedactingProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (isset($context['request_uri']) && is_string($context['request_uri'])) {
            $context['request_uri'] = preg_replace('#/(confirm|unsubscribe)/[A-Za-z0-9_-]{43}#', '/$1/<redacted>', $context['request_uri']);
        }
        if (isset($context['token'])) {
            $context['token'] = '<redacted>';
        }
        return $record->with(context: $context);
    }
}
