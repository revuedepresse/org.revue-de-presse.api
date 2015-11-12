<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Symfony\Component\Form\FormInterface;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractController
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $entityManager;

    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    public $formFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    public $requestStack;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    public $router;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    public $session;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    public $tokenStorage;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

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

    /**
     * @return array
     */
    protected function getCurrentRoute()
    {
        $request = $this->requestStack->getMasterRequest();

        return $this->router->generate($request->attributes->get('_route'));
    }

    /**
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            throw new AccessDeniedHttpException();
        }

        /**
         * @var \Symfony\Component\Security\Core\User\UserInterface $user
         */
        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            throw new AccessDeniedHttpException();
        }

        return $user;
    }

    /**
     * @param FormInterface $form
     * @param $action
     * @param null $domain
     */
    protected function handleFormErrors(FormInterface $form, $action, $domain = null)
    {
        if (is_null($domain)) {
            $domain = 'oauth';
        }

        $flashBag = [$this->translator->trans($domain . '.' . $action . '.error', [], 'oauth')];

        $this->addErrorFlashMessages($form, $flashBag, $action . '_error');
    }

    /**
     * @param FormInterface $form
     * @param array $flashBag
     * @param $type
     */
    protected function addErrorFlashMessages(FormInterface $form, array $flashBag, $type)
    {
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $flashBag['error'][] = $error->getMessage();
        }

        $this->addFlashMessages($flashBag, $type);
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
}
