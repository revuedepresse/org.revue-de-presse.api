<?php
declare(strict_types=1);

namespace App\Conversation\Exception;

use function implode;

/**
 * @package App\Conversation\Exception
 */
class InvalidStatusException extends \Exception
{
    public const ERROR_CODE_MISSING_ORIGINAL_DOCUMENT = 10;
    public const ERROR_CODE_MISSING_STATUS_ID         = 20;
    public const ERROR_CODE_MISSING_TEXT              = 30;

    /**
     * @throws InvalidStatusException
     */
    public static function missingOriginalDocument(): void
    {
        throw new self(implode([
            'Invalid status because of missing original document.'
        ]), self::ERROR_CODE_MISSING_ORIGINAL_DOCUMENT);
    }

    /**
     * @throws InvalidStatusException
     */
    public static function missingStatusId(): void
    {
        throw new self(implode([
            'Invalid status because of missing status id.'
        ]), self::ERROR_CODE_MISSING_STATUS_ID);
    }

    /**
     * @throws InvalidStatusException
     */
    public static function missingText(): void
    {
        throw new self(implode([
           'Invalid status because of missing text.'
       ]), self::ERROR_CODE_MISSING_TEXT);
    }

    /**
     * @return bool
     */
    public function wasThrownBecauseOfMissingOriginalDocument(): bool
    {
        return $this->code === self::ERROR_CODE_MISSING_ORIGINAL_DOCUMENT;
    }
}