<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class TwitterConnectController
 * @package WeavingTheWeb\Bundle\UserBundle\Controller
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/twitter")
 */
class TwitterController extends ContainerAware
{
    /**
     * @Extra\Route("/connect", name="weaving_the_web_user_twitter_connect")
     */
    public function connectAction()
    {
        /**
         * @var $twitter \FOS\TwitterBundle\Services\Twitter
         */
        $authURL = $this->container->get('fos_twitter.service')->getLoginUrl();

        return new RedirectResponse($authURL);
    }

    /**
     * @Extra\Route("/login_check", name="weaving_the_web_user_twitter_login_check")
     */
    public function checkLoginAction()
    {
    }

    /**
     * @Extra\Route("/could-not-login", name="weaving_the_web_user_twitter_failure")
     */
    public function denyLoginAction()
    {
    }

    /**
     * @Extra\Route("/access-token", name="weaving_the_web_user_twitter_get_access_token")
     */
    public function getAccessTokenAction()
    {
        /**
         * @var $request \Symfony\Component\HttpFoundation\Request
         */
        $request = $this->container->get('request');
        $oauthToken = $request->get('oauth_token');
        $oauthVerifier = $request->get('oauth_verifier');

        $twitter = $this->container->get('fos_twitter.service');
        $accessToken = $twitter->getAccessToken($oauthToken, $oauthVerifier);

        $response = new Response();
        $response->setContent(json_encode($accessToken));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
