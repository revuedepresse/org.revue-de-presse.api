<?php

namespace App\Conversation\Consistency;

use Safe\Exceptions\JsonException;
use function array_key_exists;
use function Safe\json_decode;

/**
 * @package App\Conversation\Consistency
 */
class StatusConsistency
{
    /**
     * @throws JsonException
     */
    public static function fillMissingStatusProps(
        string $json,
        array $status
    ): array {
        $decodedJson = json_decode($json, assoc: true);

        $status['status_id'] = $decodedJson['id_str'];

        if (array_key_exists('full_text', $decodedJson)) {
            $status['text'] = $decodedJson['full_text'];

            return $status;
        }

        $status['text'] = $decodedJson['text'];

        return $status;
    }
}