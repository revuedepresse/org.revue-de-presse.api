<?php
declare(strict_types=1);

namespace App\Twitter\Exception;

/**
 * @package App\Twitter\Exception
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