<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Decorator;

use App\Twitter\Infrastructure\Publication\Exception\DocumentException;
use function array_key_exists;
use function json_decode;
use function json_last_error;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class StatusDecorator implements StatusDecoratorInterface
{
    public static function decorateStatus(array $status): array
    {
        return array_map(function ($status) {
            if ($status['original_document'] === '') {
                DocumentException::throwsEmptyDocumentException((int) $status['id']);
            }

            $decodedValue  = json_decode(
                $status['original_document'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $lastJsonError = json_last_error();

            if (
                $lastJsonError !== JSON_ERROR_NONE ||
                !array_key_exists('retweeted_status', $decodedValue)
            ) {
                DocumentException::throwsDecodingException(
                    $lastJsonError,
                    (int) $status['id']
                );
            }

            if (!isset($decodedValue['retweeted_status']['user']['screen_name'],
                $decodedValue['retweeted_status']['full_text'])) {
                DocumentException::throwsInvalidProperties(
                    (int) $status['id']
                );
            }

            $status['text'] = implode([
                'RT @',
                $decodedValue['retweeted_status']['user']['screen_name'],
                ': ',
                $decodedValue['retweeted_status']['full_text']
            ]);

            return $status;
        }, $status);
    }
}