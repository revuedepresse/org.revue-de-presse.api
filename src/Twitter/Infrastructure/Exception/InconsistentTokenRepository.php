<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

/**
 * @package App\Twitter\Infrastructure\Exception
 */
class InconsistentTokenRepository extends \Exception
{
    public static function onEmptyAvailableTokenList()
    {
        throw new self(implode([
            'There should be at least an access token available. ',
            'Please check the database consistency.'
        ]));
    }
}