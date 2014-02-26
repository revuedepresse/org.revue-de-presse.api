<?php

namespace WTW\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Http\Logout\LogoutHandlerInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class SecurityController
 *
 * @Extra\Route(service="wtw.user.security_controller")
 * @package WTW\DashboardBundle\Controller
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SecurityController extends BaseController implements LogoutHandlerInterface
{
    /**
     * @Extra\Route("/basic/logout", name="wtw_dashboard_logout_basic")
     */
    public function logout(Request $request, Response $response = null, TokenInterface $token = null)
    {
        if (!is_null($response)) {
            $request->getSession()->set('requested_logout', true);
        } else {
            $homepageUrl = $this->container->get('router')->generate('fos_user_registration_register');

            return new RedirectResponse($homepageUrl, 302);
        }
    }

    public function loginAction(Request $request)
    {
        $path['_controller'] = 'WTWUserBundle:Registration:land';
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
