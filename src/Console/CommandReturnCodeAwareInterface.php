<?php
declare(strict_types=1);

namespace App\Console;

interface CommandReturnCodeAwareInterface
{
    public const RETURN_STATUS_SUCCESS = 0;

    public const RETURN_STATUS_FAILURE = 1;
}
