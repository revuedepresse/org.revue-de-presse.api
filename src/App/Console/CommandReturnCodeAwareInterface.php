<?php

namespace App\Console;

interface CommandReturnCodeAwareInterface
{
    const RETURN_STATUS_SUCCESS = 0;

    const RETURN_STATUS_FAILURE = 1;
}
