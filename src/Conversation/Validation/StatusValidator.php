<?php
declare(strict_types=1);

namespace App\Conversation\Validation;

use App\Conversation\Exception\InvalidStatusException;
use function array_key_exists;

/**
 * @package App\Conversation\Validation
 */
class StatusValidator
{
    /**
     * @param array $status
     *
     * @return array
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingOriginalDocument(
        array $status
    ): array {
        if (!array_key_exists('original_document', $status)) {
            InvalidStatusException::missingOriginalDocument();
        }

        return $status;
    }

    /**
     * @param array $status
     *
     * @return array
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingStatusId(
        array $status
    ): array {
        if (!array_key_exists('status_id', $status)) {
            InvalidStatusException::missingStatusId();
        }

        return $status;
    }

    /**
     * @param array $status
     *
     * @return array
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingText(
        array $status
    )
    : array {
        if (!array_key_exists('text', $status)) {
            InvalidStatusException::missingText();
        }

        return $status;
    }
}