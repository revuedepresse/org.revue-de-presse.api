<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Core\Validator\Constraints\UserPassword,
    Symfony\Component\Form\FormError,
    Symfony\Component\HttpKernel\HttpKernelInterface;

use WTW\UserBundle\Entity\User;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SettingsController extends ContainerAware
{
    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var \Symfony\Component\Form\FormFactory $formFactory
     */
    protected $formFactory;

    /**
     * @var \Symfony\Component\HttpKernel\Kernel $kernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\HttpFoundation\Request $request
     */
    protected $request;

    /**
     * @var \Symfony\Component\Routing\Router $router
     */
    protected $router;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    protected $securityContext;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    protected $session;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    protected $tokenRepository;

    /**
     * @var \Symfony\Component\Translation\Translator $translator
     */
    protected $translator;

    /**
     * @var \WeavingTheWeb\Bundle\UserBundle\Services\Twitter $twitter
     */
    protected $twitter;

    /**
     * @var \WeavingTheWeb\Bundle\UserBundle\Doctrine\UserManager $userManager
     */
    protected $userManager;

    /**
     * @var \Symfony\Component\Validator\Validator $validator
     */
    protected $validator;

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Symfony\Component\Form\FormFactory $formFactory
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Kernel $kernel
     */
    public function setKernel($kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param \Symfony\Component\Routing\Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    public function setSession($session)
    {
        $this->session = $session;
    }

    public function setSecurityContext($securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    public function setTokenRepository($tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * @param \Symfony\Component\Validator\Validator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \WeavingTheWeb\Bundle\UserBundle\Doctrine\UserManager $userManager
     */
    public function setUserManager($userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     *
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
                $errorList = $this->validator->validateValue($currentPassword, new UserPassword());

                if (count($errorList) === 0) {
                    $plainPassword = $form->get('plainPassword')->getData();
                    if (strlen(trim($plainPassword)) === 0) {
                        $currentUser->setPlainPassword($currentPassword);
                    }
                    $this->userManager->updateUser($currentUser);

                    return new RedirectResponse($this->router->generate('weaving_the_web_user_show_settings'));
                } else {
                    $currentPasswordError = $this->translator->trans('field_error_current_password', [], 'user');
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
        return $this->formFactory->create('user', $user);
    }

    /**
     * @return mixed
     */
    protected function getUser()
    {
        return $this->securityContext->getToken()->getUser();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectToTwitterAction()
    {
        try {
            $authURL = $this->twitter->getLoginUrl();
        } catch (\RuntimeException $exception) {
            $this->session->getFlashBag()->add('error', $this->translator->trans($exception->getMessage(), [], 'user'));

            return $this->goToSettingsAction();
        }

        return new RedirectResponse($authURL);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response $response
     */
    public function saveTokenAction()
    {
        if (!$this->request->query->has('oauth_token') || !$this->request->query->has('oauth_verifier')) {
            return $this->goToSettingsAction();
        }

        $path['_controller'] = 'weaving_the_web_user.controller.twitter:getAccessTokenAction';

        $subRequest = $this->request->duplicate([
            'oauth_token' => $this->request->get('oauth_token'),
            'oauth_verifier' => $this->request->get('oauth_verifier')
        ], null, $path);

        /**
         * @var \Symfony\Component\HttpFoundation\Response $response
         */
        $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $content = $response->getContent();
        $tokenParameters = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->goToSettingsAction();
        }
        $this->persistToken($tokenParameters);

        $subRequest = $this->request->duplicate(null, null,
            ['_controller' => 'weaving_the_web_user.controller.settings:showAction']);

        return $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @return RedirectResponse
     */
    protected function goToSettingsAction()
    {
        return new RedirectResponse($this->router->generate('weaving_the_web_user_show_settings'));
    }

    /**
     * @param $tokenParameters
     * @throws \Exception
     */
    protected function persistToken($tokenParameters)
    {
        if ($tokenParameters['screen_name'] !== $this->getUser()->getTwitterUserName()) {
            throw new \Exception('The token doesn\'t match ' .
                'with the declared twitter username of current user');
        }

        $tokens = $this->tokenRepository->findBy(['oauthToken' => $tokenParameters['oauth_token']]);

        if (count($tokens) === 0) {
            $token = $this->tokenRepository->makeToken($tokenParameters);

            /** @var \WTW\UserBundle\Entity\User $user */
            $user = $this->getUser();

            $phantomUser = $this->userManager->findUserBy([
                'twitter_username' => $tokenParameters['screen_name'],
                'twitterID' => $tokenParameters['user_id']
            ]);
            if (!is_null($phantomUser)) {
                $phantomUser->setTwitterID(null);
                $this->entityManager->persist($phantomUser);
                $this->entityManager->flush();
            }

            $this->userManager->updateUserTwitterCredentials($user, $tokenParameters);

            $token->addUser($user);
            $this->entityManager->persist($token);
            $this->entityManager->flush();

            $user->addToken($token);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $noticeMessage = 'successful_access_token_persistence';
        } else {
            $noticeMessage = 'existing_access_token';
        }

        $this->session->getFlashBag()->add(
           'notice', $noticeMessage
        );
    }
}