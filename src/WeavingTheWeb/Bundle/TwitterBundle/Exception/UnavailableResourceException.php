<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Exception;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Exception
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UnavailableResourceException extends \Exception
{
    const EXCEPTION_CODE_COULD_NOT_RESOLVE_HOST = 6;
}
