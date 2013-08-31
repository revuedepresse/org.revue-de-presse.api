<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Core\Validator\Constraints\UserPassword,
    Symfony\Component\Form\FormError,
    Symfony\Component\HttpKernel\HttpKernelInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use WTW\UserBundle\Entity\User;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;

/**
 * Class SettingsController
 * @package WeavingTheWeb\Bundle\UserBundle\Controller
 */
class SettingsController extends ContainerAware
{
    /**
     * @param Request $request
     * @Extra\Template("WeavingTheWebUserBundle:Settings:show.html.twig")
     */
    public function saveAction(Request $request)
    {
        $currentUser = $this->getUser();
        $form = $this->getSettingsForm($currentUser);
        $form->handleRequest($request);

        if ($request->getMethod() === 'POST') {
            if ($form->isValid()) {
                $currentPassword = $form->get('currentPassword')->getData();

                /**
                 * @var $validator \Symfony\Component\Validator\Validator
                 */
                $validator = $this->container->get('validator');
                $errorList = $validator->validateValue($currentPassword, new UserPassword());

                if (count($errorList) === 0) {
                    $plainPassword = $form->get('plainPassword')->getData();
                    if (strlen(trim($plainPassword)) === 0) {
                        $currentUser->setPlainPassword($currentPassword);
                    }

                    /**
                     * @var $userManager \FOS\UserBundle\Model\UserManagerInterface
                     */
                    $userManager = $this->container->get('weaving_the_web_user.user_manager');
                    $userManager->updateUser($currentUser);

                    return new RedirectResponse($this->container->get('router')
                        ->generate('weaving_the_web_user_show_settings'));
                } else {
                    $translator = $this->container->get('translator');
                    $currentPasswordError = $translator->trans('field_error_current_password', [], 'user');
                    $form->get('currentPassword')->addError(new FormError($currentPasswordError));
                }
            }
        }

        return ['form' => $form->createView()];
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Extra\Template("WeavingTheWebUserBundle:Settings:show.html.twig")
     */
    public function showAction()
    {
        $user = $this->getUser();
        $form = $this->getSettingsForm($user);

        return ['form' => $form->createView()];
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function getSettingsForm(User $user)
    {
        /**
         * @var $formFactory \Symfony\Component\Form\FormFactory
         */
        $formFactory = $this->container->get('form.factory');

        return $formFactory->create('user', $user);
    }

    /**
     * @return mixed
     */
    protected function getUser()
    {
        /**
         * @var $securityContext \Symfony\Component\Security\Core\SecurityContext
         */
        $securityContext = $this->container->get('security.context');

        return $securityContext->getToken()->getUser();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectToTwitterAction()
    {
        /**
         * @var $twitter \FOS\TwitterBundle\Services\Twitter
         */
        $authURL = $this->container->get('weaving_the_web_user.twitter')->getLoginUrl();

        return new RedirectResponse($authURL);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response $response
     */
    public function saveTokenAction()
    {
        /**
         * @var \Symfony\Component\HttpFoundation\Request $request
         */
        $request = $this->container->get('request');

        if (!$request->query->has('oauth_token') || !$request->query->has('oauth_token_secret')) {
            $router = $this->container->get('router');
            $showSettingsUrl = $router->generate('weaving_the_web_user_show_settings');

            return new RedirectResponse($showSettingsUrl);
        }

        $path['_controller'] = 'WeavingTheWebUserBundle:Twitter:getAccessToken';

        $subRequest = $request ->duplicate([
                'oauth_token' => $request->get('oauth_token'),
                'oauth_verifier' => $request->get('oauth_verifier')
            ], null, $path);

        /**
         * @var \Symfony\Component\HttpFoundation\Response $response
         */
        $response = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $content = $response->getContent();
        $tokenParameters = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Access token could not be retrieved');
        }
        $this->persistToken($tokenParameters);

        /**
         * @var \Symfony\Component\HttpFoundation\Request $request
         */
        $request = $this->container->get('request');
        $subRequest = $request ->duplicate([], [], ['_controller' => 'WeavingTheWebUserBundle:Settings:show']);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @param $tokenParameters
     */
    protected function persistToken($tokenParameters)
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $tokenRepository = $entityManager->getRepository('WeavingTheWebApiBundle:Token');
        $tokens = $tokenRepository->findBy(['oauthToken' => $tokenParameters['oauth_token']]);

        if (count($tokens) === 0) {
            if ($tokenParameters['oauth_token']);
            $token = new Token();

            $now = new \DateTime();
            $token->setCreatedAt($now);
            $token->setUpdatedAt($now);

            $token->setOauthToken($tokenParameters['oauth_token']);
            $token->setOauthTokenSecret($tokenParameters['oauth_token_secret']);

            /**
             * @var \WTW\UserBundle\Entity\User $user
             */
            $user = $this->getUser();
            $user->setTwitterUsername($tokenParameters['screen_name']);
            $user->setTwitterID($tokenParameters['user_id']);
            $token->addUser($user);

            $entityManager->persist($token);
            $entityManager->flush();

            $user->addToken($token);

            $entityManager->persist($user);
            $entityManager->flush();
        }
    }
}