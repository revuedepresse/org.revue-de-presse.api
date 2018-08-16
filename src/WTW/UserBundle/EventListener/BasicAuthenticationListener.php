<?php

namespace WTW\UserBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener as AuthenticationListener,
    Symfony\Component\HttpKernel\Log\LoggerInterface,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Class BasicAuthenticationListener
 *
 * @package WTW\DashboardBundle\EventListener
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class BasicAuthenticationListener extends AuthenticationListener
{
    protected $tokenStorage;

    protected $authenticationEntryPoint;

    public function __construct(
        TokenStorage $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        $providerKey,
        AuthenticationEntryPointInterface $authenticationEntryPoint,
        LoggerInterface $logger = null)
    {
        parent::__construct(
            $tokenStorage,
            $authenticationManager,
            $providerKey,
            $authenticationEntryPoint,
            $logger);

        $this->tokenStorage = $tokenStorage;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        parent::handle($event);

        $request = $event->getRequest();
        $session = $request->getSession();

        if ($session->has('requested_logout')) {
            $session->invalidate();
            $this->tokenStorage->setToken(null);
            $event->setResponse($this->authenticationEntryPoint->start($request));
        }
    }
}
