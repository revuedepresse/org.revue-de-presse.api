<?php

namespace WTW\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Http\Logout\LogoutHandlerInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Templating\EngineInterface;

/**
 * Class SecurityController
 *
 * @Extra\Route(service="wtw.user.security_controller")
 * @package WTW\DashboardBundle\Controller
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SecurityController implements LogoutHandlerInterface
{
    /**
     * @var $templating EngineInterface
     */
    protected $templating;

    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @Extra\Route("/basic/logout", name="wtw_dashboard_logout_basic")
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $request->getSession()->set('requested_logout', true);
    }
}