<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Settings\OAuth;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\Form\FormInterface;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use WeavingTheWeb\Bundle\DashboardBundle\Controller\AbstractController,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @Extra\Route("/settings/oauth", service="weaving_the_web_dashboard.controller.settings.oauth")
 */
class OAuthController extends AbstractController
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
     * @var \Symfony\Component\HttpKernel\HttpKernel
     */
    public $httpKernel;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Form\Type\OAuth\SelectClientType
     */
    public $selectOAuthClientType;

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

        $oauthClientSelectionResponse = $this->selectOAuthClientAction($request);
        if ($oauthClientSelectionResponse instanceof RedirectResponse) {
            return $oauthClientSelectionResponse;
        }

        return array_merge(
            $oauthClientResponse,
            $oauthClientRegistrationResponse,
            $oauthClientSelectionResponse,
            ['active_menu_item' => 'oauth_settings']
        );
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
            if ($form->isValid()) {
                $data = $form->getData();
                /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client $client */
                $redirectUri = $data['redirect_uri'];
                $client = $this->clientRepository->make($redirectUri);

                $infoMessages = [$this->translator->trans(
                    'oauth.create_client.success',
                    [],
                    'oauth'
                )];
                $this->addFlashMessages($infoMessages, 'create_client_info');

                $linkMessages = [$this->translator->trans(
                    'oauth.create_client.new_client',
                    [
                        '{{ authorization_url }}' => $client->getAuthorizationUrl(),
                        '{{ client_id }}' => $client->getPublicId(),
                        '{{ client_secret }}' => $client->getSecret(),
                    ],
                    'oauth'
                )];
                $this->addFlashMessages($linkMessages, 'create_client_link');

                return new RedirectResponse($currentRoute);
            } else {
                $this->handleFormErrors($form, 'create_client');
            }
        }

        return ['create_oauth_client_form' => $form->createView()];
    }

    /**
     * @Extra\Route(
     *      "/client/register",
     *      name="weaving_the_web_dashboard_settings_oauth_register_client"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:OAuthClient/Register/_form.html.twig")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerOAuthClientAction(Request $request)
    {
        $user = $this->getUser();

        $currentRoute = $this->getCurrentRoute();

        $form = $this->formFactory->create(
            'register_oauth_client',
            ['redirect_uri' => $this->getDefaultRedirectUri()],
            ['action' => $currentRoute]
        );

        if ($this->isFormSubmitted($form, $request)) {
            if ($form->isValid()) {
                $data = $form->getData();

                /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client $client */
                $client = $this->clientRegistry->make(
                    $data['client_id'],
                    $data['client_secret'],
                    $data['redirect_uri']
                );
                $client->setUser($user);

                $this->entityManager->persist($client);
                $this->entityManager->flush();

                $flashMessages = [$this->translator->trans('oauth.register_client.success', [], 'oauth')];
                $this->addFlashMessages($flashMessages, 'register_client_info');

                return new RedirectResponse($currentRoute);
            } else {
                $this->handleFormErrors($form, 'register_client');
            }
        }

        return ['register_oauth_client_form' => $form->createView()];
    }

    /**
     * @Extra\Route(
     *      "/client/select",
     *      name="weaving_the_web_dashboard_settings_oauth_select_client"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:OAuthClient/Select/_form.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function selectOAuthClientAction(Request $request) {
        $currentRoute = $this->getCurrentRoute();

        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client $client */
        $client = $this->clientRegistry->findOneby(['selected' => true, 'user' => $this->getUser()]);

        $data = null;
        if (!is_null($client)) {
            $data = ['oauth_clients' => $client];
        }
        $this->selectOAuthClientType->setUser($this->getUser());
        $form = $this->formFactory->create(
            $this->selectOAuthClientType,
            $data,
            [
                'action' => $currentRoute,
            ]
        );

        if ($this->isFormSubmitted($form, $request)) {
            if ($form->isValid()) {
                /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client $submittedOAuthClient */
                $submittedOAuthClient = $form->get('oauth_clients')->getData();

                // Unselect the previously selected OAuth client
                if (!is_null($data)) {
                    $client->unselect();
                    $this->updateOAuthClientSelection($client);
                }

                if (is_null($submittedOAuthClient)) {
                    $messageKey = 'empty_selection';
                } else {
                    $submittedOAuthClient->select();
                    $this->updateOAuthClientSelection($submittedOAuthClient);

                    $messageKey = 'success';
                }

                $successMessage = $this->translator->trans('oauth.select_client.' . $messageKey, [], 'oauth');
                $this->addFlashMessages([$successMessage], 'select_client_info');

                return new RedirectResponse($currentRoute);
            } else {
                $this->handleFormErrors($form, 'select_client');
            }
        }

        return ['select_oauth_client_form' => $form->createView()];
    }

    /**
     * @return string
     */
    protected function getDefaultRedirectUri()
    {
        return 'http://localhost' . $this->router->generate('weaving_the_web_api_oauth_callback');
    }

    /**
     * @param Client $oauthClient
     */
    protected function updateOAuthClientSelection(Client $oauthClient)
    {
        $this->entityManager->persist($oauthClient);
        $this->entityManager->flush();
    }
}
