<?php

namespace WTW\UserBundle\Controller;

use Doctrine\DBAL\DBALException;
use FOS\UserBundle\Controller\RegistrationController as BaseController,
    FOS\UserBundle\Event\UserEvent,
    FOS\UserBundle\FOSUserEvents,
    FOS\UserBundle\Event\FormEvent,
    FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\Form\FormError,
    Symfony\Component\Form\FormInterface,
    Symfony\Component\Validator\Constraints\Email,
    Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationController extends BaseController
{
    /**
     * @param  Request          $request
     * @return RedirectResponse
     * @Extra\Template("WTWUserBundle:Registration:register.html.twig")
     */
    public function landAction(Request $request)
    {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $exceptions = [];

        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));
        $loginFormParameters = $this->getLoginFormParameters($request);

        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->container->get('fos_user.registration.form.factory');
        /** @var $form \Symfony\Component\Form\Form */
        $form = $formFactory->createForm();
        $form->setData($user);

        if ('POST' === $request->getMethod()) {
            try {
                $response = $this->getRegistrationFormResponse($request, $form, $user);
            } catch (DBALException $exception) {
                $message = $exception->getMessage();

                /** @var $logger \Psr\Log\LoggerInterface; */
                $logger = $this->container->get('logger');
                $logger->error($message);

                if (false !== strpos($message, "'usr_user_name'")) {
                    $exceptions[] = 'error_username_already_taken';
                }
                if (false !== strpos($message, "'usr_email'")) {
                    $exceptions[] = 'error_email_already_taken';
                }
            }

            if (isset($response) && !is_null($response)) {
                return $response;
            }
        }

        return array_merge(
            $loginFormParameters, [
                'form' => $form,
                'exceptions' => $exceptions
            ]
        );
    }

    protected function getLoginFormParameters(Request $request)
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            $translator = $this->container->get('translator');
            $error = $translator->trans($error->getMessageKey(), [], 'security');
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        $csrfToken = $this->container->has('form.csrf_provider')
            ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
            : null;

        return array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'csrf_token' => $csrfToken,
        );
    }

    /**
     * @param Request $request
     * @param         $form
     * @param         $user
     *
     * @return RedirectResponse
     */
    protected function getRegistrationFormResponse(Request $request, $form, $user)
    {
        $response = null;

        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $form->handleRequest($request);

        $this->validatePassword($form);
        $this->validateEmail($form);
        $this->validateUsername($form);

        if ($form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

            $user->setEnabled(false);
            $user->setLocked(false);
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->container->get('router')->generate('fos_user_registration_confirmed');
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(
                FOSUserEvents::REGISTRATION_COMPLETED,
                new FilterUserResponseEvent($user, $request, $response)
            );
        }

        return $response;
    }

    /**
     * @param FormInterface $form
     */
    protected function validatePassword(FormInterface  $form)
    {
        $plainPassword = $form->get('plainPassword')->getData();
        if (is_null($plainPassword) || (strlen(trim($plainPassword)) === 0)) {
            $translator = $this->container->get('translator');
            $emptyPassword = $translator->trans('registration.password.empty', [], 'security');
            $form->get('plainPassword')->addError(new FormError($emptyPassword));
        }
    }

    /**
     * @param FormInterface $form
     */
    protected function validateEmail(FormInterface $form)
    {
        $translator = $this->container->get('translator');
        $email = $form->get('email');
        $emailValue = $email->getData();
        $emailConstraint = new Email();
        $emailConstraint->message = $translator->trans('registration.email.invalid', [], 'security');
        $notBlankConstraint = new NotBlank();
        $notBlankConstraint->message = $translator->trans('registration.email.blank', [], 'security');

        /**
         * @var $validator \Symfony\Component\Validator\Validator
         */
        $validator = $this->container->get('validator');
        $errorList = $validator->validateValue(
            $emailValue,
            [
                $notBlankConstraint,
                $emailConstraint
            ]
        );
        if (count($errorList) > 0) {
            $email->addError(new FormError($errorList[0]));
        }
    }

    /**
     * @param FormInterface $form
     */
    protected function validateUsername(FormInterface  $form)
    {
        $username = $form->get('username');
        $usernameValue = $username->getData();
        $notBlankConstraint = new NotBlank();

        $translator = $this->container->get('translator');
        $notBlankConstraint->message = $translator->trans('registration.username.blank', [], 'security');

        /**
         * @var $validator \Symfony\Component\Validator\Validator
         */
        $validator = $this->container->get('validator');
        $errorList = $validator->validateValue($usernameValue, $notBlankConstraint);
        if (count($errorList) > 0) {
            $username->addError(new FormError($errorList[0]));
        }
    }
}
