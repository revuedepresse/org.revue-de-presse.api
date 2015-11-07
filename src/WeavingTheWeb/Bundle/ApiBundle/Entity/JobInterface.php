<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface JobInterface
{
    const STATUS_IDLE = 10;

    const STATUS_STARTED = 20;

    const STATUS_FINISHED = 30;

    const STATUS_FAILED = 40;

    const TYPE_COMMAND = 0;
}
