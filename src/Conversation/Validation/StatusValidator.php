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
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingOriginalDocument(array $tweet): array {
        if (!array_key_exists('original_document', $tweet)) {
            InvalidStatusException::missingOriginalDocument();
        }

        return $tweet;
    }

    /**
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingStatusId(array $tweet): array {
        if (!array_key_exists('status_id', $tweet)) {
            InvalidStatusException::missingStatusId();
        }

        return $tweet;
    }

    /**
     * @throws InvalidStatusException
     */
    public static function guardAgainstMissingText(array $tweet)
    : array {
        if (!array_key_exists('text', $tweet)) {
            InvalidStatusException::missingText();
        }

        return $tweet;
    }
}