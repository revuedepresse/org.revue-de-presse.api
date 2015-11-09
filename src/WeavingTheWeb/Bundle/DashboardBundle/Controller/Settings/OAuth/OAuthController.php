<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Settings\OAuth;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\Form\FormInterface;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/settings/oauth", service="weaving_the_web_dashboard.controller.settings.oauth")
 */
class OAuthController
{
    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\OAuth\ClientRepository
     */
    public $clientRepository;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\OAuth\ClientRepository
     */
    public $clientRegistry;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $entityManager;

    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    public $formFactory;

    /**
     * @var \Symfony\Component\HttpKernel\HttpKernel
     */
    public $httpKernel;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    public $router;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    public $session;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    public $requestStack;

    /**
     * @Extra\Route(
     *      "/",
     *      name="weaving_the_web_dashboard_settings_oauth_show_settings"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:OAuthClient/Create/_block.html.twig")
     * @param  Request $request
     * @return array
     */
    public function showSettingsAction(Request $request)
    {
        $oauthClientResponse = $this->createOAuthClientAction($request);
        if ($oauthClientResponse instanceof RedirectResponse) {
            return $oauthClientResponse;
        }

        $oauthClientRegistrationResponse = $this->registerOAuthClientAction($request);
        if ($oauthClientRegistrationResponse instanceof RedirectResponse) {
            return $oauthClientRegistrationResponse;
        }

        return array_merge($oauthClientResponse, $oauthClientRegistrationResponse);
    }

    public function forward($controller, array $path = [], array $query = []) {
        $path['_controller'] = $controller;
        $subRequest = $this->requestStack->getCurrentRequest()->duplicate($query, null, $path);

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @Extra\Route(
     *      "/client/create",
     *      name="weaving_the_web_dashboard_settings_oauth_create_client"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:OAuthClient/Create/_block.html.twig")
     *
     * @param  Request $request
     * @return array
     */
    public function createOAuthClientAction(Request $request)
    {
        $currentRoute = $this->getCurrentRoute();
        $form = $this->formFactory->create(
            'create_oauth_client',
            ['redirect_uri' => $this->getDefaultRedirectUri()],
            ['action' => $currentRoute]
        );

        if ($this->isFormSubmitted($form, $request)) {
            $flashBag = [
                'error' => [],
                'info' => [],
                'link' => [],
            ];

            if ($form->isValid()) {
                $data = $form->getData();
                /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client $client */
                $redirectUri = $data['redirect_uri'];
                $client = $this->clientRepository->make($redirectUri);

                $flashBag['info'][] = $this->translator->trans(
                    'oauth.create_client.success',
                    [],
                    'oauth'
                );

                $flashBag['link'][] = $this->translator->trans(
                    'oauth.create_client.new_client',
                    [
                        '{{ authorization_url }}' => $client->getAuthorizationUrl(),
                        '{{ client_id }}' => $client->getPublicId(),
                        '{{ client_secret }}' => $client->getSecret(),
                    ],
                    'oauth'
                );

                $this->addFlashMessages($flashBag['info'], 'creation_info');
                $this->addFlashMessages($flashBag['link'], 'creation_link');

                return new RedirectResponse($currentRoute);
            } else {
                $errorMessage = $this->translator->trans('oauth.create_client.error', [], 'oauth');
                $flashBag['error'][] = $errorMessage;

                $this->addErrorFlashMessages($form, $flashBag, 'creation_error');
            }
        }

        return ['create_oauth_client_form'  => $form->createView()];
    }

    /**
     * @param array $messages
     * @param $type
     */
    protected function addFlashMessages(array $messages, $type)
    {
        if (count($messages) > 0) {
            $this->session->getFlashBag()->add(
                $type,
                implode("\n", $messages)
            );
        }
    }

    /**
     * @Extra\Route("/client/register", name="weaving_the_web_dashboard_settings_oauth_register_client")
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:OAuthClient/Register/_form.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function registerOAuthClientAction(Request $request)
    {
        $currentRoute = $this->getCurrentRoute();

        $form = $this->formFactory->create(
            'register_oauth_client',
            ['redirect_uri' => $this->getDefaultRedirectUri()],
            ['action' => $currentRoute]
        );

        if ($this->isFormSubmitted($form, $request)) {
            $flashBag = [];

            if ($form->isValid()) {
                $data = $form->getData();

                /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client $client */
                $client = $this->clientRegistry->make(
                    $data['client_id'],
                    $data['client_secret'],
                    $data['redirect_uri']
                );

                $this->entityManager->persist($client);
                $this->entityManager->flush();

                $flashBag['info'][] = $this->translator->trans('oauth.register_client.success', [], 'oauth');
                $this->addFlashMessages($flashBag['info'], 'registration_info');

                return new RedirectResponse($currentRoute);
            } else {
                $errorMessage = $this->translator->trans('oauth.register_client.error', [], 'oauth');
                $flashBag['error'][] = $errorMessage;

                $this->addErrorFlashMessages($form, $flashBag, 'registration_error');
            }
        }

        return ['register_oauth_client_form'    => $form->createView()];
    }

    /**
     * @return string
     */
    protected function getDefaultRedirectUri()
    {
        return 'http://localhost' . $this->router->generate('weaving_the_web_api_oauth_callback');
    }

    /**
     * @return array
     */
    protected function getCurrentRoute()
    {
        $request = $this->requestStack->getMasterRequest();

        return $this->router->generate($request->attributes->get('_route'));
    }

    /**
     * @param FormInterface $form
     * @param array $flashBag
     */
    protected function addErrorFlashMessages(FormInterface $form, array $flashBag, $type)
    {
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $flashBag['error'][] = $error->getMessage();
        }

        $this->addFlashMessages($flashBag['error'], $type);
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return bool
     */
    protected function isFormSubmitted(FormInterface $form, Request $request)
    {
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            return $form->isSubmitted();
        } else {
            return false;
        }
    }
}
