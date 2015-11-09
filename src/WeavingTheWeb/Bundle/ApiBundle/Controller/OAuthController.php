<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/oauth/v2")
 */
class OAuthController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @Extra\Route("/callback", name="weaving_the_web_api_oauth_callback")
     */
    public function callbackAction(Request $request)
    {
        $redirectUri = $request->getSchemeAndHttpHost() . $this->generateUrl('weaving_the_web_api_oauth_callback');

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $tokenUrl = $router->generate(
            'fos_oauth_server_token', [
                'client_id' => $this->container->getParameter('api_client_id'),
                'client_secret' => $this->container->getParameter('api_client_secret'),
                'code' => $request->query->get('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]
        );

        return new RedirectResponse($tokenUrl);
    }
}
