<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Exception;

use function sprintf;

class DocumentException extends \Exception
{
    public const  EXCEPTION_CODE_EMPTY_DOCUMENT   = 10;
    public const  EXCEPTION_CODE_INVALID_PROPERTY = 20;

    public static function throwsEmptyDocumentException(int $statusId): void
    {
        throw new self(
            sprintf(
                'Empty document for status of id #%d',
                $statusId
            ),
            self::EXCEPTION_CODE_EMPTY_DOCUMENT
        );
    }

    public static function throwsDecodingException(
        int $jsonErrorCode,
        int $statusId
    ): void {
        throw new self(
            sprintf(
                'JSON error #%d affecting status of id #%d',
                $jsonErrorCode,
                $statusId
            ),
            $jsonErrorCode
        );
    }

    public static function throwsInvalidProperties(int $statusId): void
    {
        throw new self(
            sprintf(
                'Invalid properties for status of id #%d',
                $statusId
            ), self::EXCEPTION_CODE_INVALID_PROPERTY
        );
    }
}