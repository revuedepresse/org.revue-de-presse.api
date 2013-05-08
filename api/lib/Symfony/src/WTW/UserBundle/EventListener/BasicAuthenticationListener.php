<?php

namespace WTW\UserBundle\EventListener;

use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener as AuthenticationListener,
    Symfony\Component\HttpKernel\Log\LoggerInterface,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface,
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
    protected $securityContext;

    protected $authenticationEntryPoint;

    public function __construct(
        SecurityContextInterface $securityContext,
        AuthenticationManagerInterface $authenticationManager,
        $providerKey,
        AuthenticationEntryPointInterface $authenticationEntryPoint,
        LoggerInterface $logger = null)
    {
        parent::__construct(
            $securityContext,
            $authenticationManager,
            $providerKey,
            $authenticationEntryPoint,
            $logger);

        $this->securityContext = $securityContext;
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
            $this->securityContext->setToken(null);
            $event->setResponse($this->authenticationEntryPoint->start($request));
        }
    }
}