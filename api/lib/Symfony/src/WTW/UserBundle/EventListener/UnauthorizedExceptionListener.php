<?php

namespace WTW\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class UnauthorizedExceptionListener
 *
 * @package WTW\UserBundle\EventListener
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UnauthorizedExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
    }
}