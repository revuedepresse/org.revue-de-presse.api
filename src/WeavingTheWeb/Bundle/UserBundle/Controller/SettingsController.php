<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Core\Validator\Constraints\UserPassword,
    Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use WTW\UserBundle\Entity\User;

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

        if ($request->getMethod() === 'POST')
        {
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
}
