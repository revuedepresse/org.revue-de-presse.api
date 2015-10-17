<?php

namespace WTW\UserBundle\EventListener;

use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener as AuthenticationListener,
    Symfony\Component\HttpKernel\Log\LoggerInterface,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @package WTW\DashboardBundle\EventListener
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class BasicAuthenticationListener extends AuthenticationListener
{
    protected $authenticationEntryPoint;

    /**
     * The token storage is exposed so that logout can be implemented by setting the authentication to null
     *
     * @var TokenStorageInterface
     */
    protected $securityTokenStorage;

    public function __construct(
        TokenStorageInterface $tokenStorage,
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

        $this->securityTokenStorage = $tokenStorage;
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
            $this->securityTokenStorage->setToken(null);
            $event->setResponse($this->authenticationEntryPoint->start($request));
        }
    }
}
