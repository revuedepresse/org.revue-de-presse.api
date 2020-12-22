<?php

namespace App\Conversation\Consistency;

use function array_key_exists;
use function Safe\json_decode;

/**
 * @package App\Conversation\Consistency
 */
class StatusConsistency
{
    /**
     * @param string $json
     * @param array  $status
     *
     * @return array
     * @throws \Safe\Exceptions\JsonException
     */
    public static function fillMissingStatusProps(
        string $json,
        array $status
    ): array {
        $decodedJson = json_decode($json, $asAssociativeArray = true);

        $status['status_id'] = $decodedJson['id_str'];

        if (array_key_exists('full_text', $decodedJson)) {
            $status['text'] = $decodedJson['full_text'];

            return $status;
        }

        $status['text'] = $decodedJson['text'];

        return $status;
    }
}