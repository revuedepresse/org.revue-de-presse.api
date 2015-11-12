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
        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\OAuth\ClientRepository $clientRepository */
        $clientRepository = $this->container->get('weaving_the_web_dashboard.repository.oauth.client');

        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client $client */
        $client = $clientRepository->findOneBy(['selected' => true]);
        $logger = $this->get('logger');

        if (is_null($client)) {
            $clientId = $this->container->getParameter('api_client_id');
            $clientSecret = $this->container->getParameter('api_client_secret');
            $logger->info(sprintf('Fallback on application-wise OAuth client (client id: "%s")', $clientId));
        } else {
            $clientId = $client->getClientId();
            $clientSecret = $client->getClientSecret();
        }

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->get('router');
        $redirectUri = $request->getSchemeAndHttpHost() . $this->generateUrl('weaving_the_web_api_oauth_callback');
        $tokenUrl = $router->generate(
            'fos_oauth_server_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $request->query->get('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]
        );

        return new RedirectResponse($tokenUrl);
    }
}
