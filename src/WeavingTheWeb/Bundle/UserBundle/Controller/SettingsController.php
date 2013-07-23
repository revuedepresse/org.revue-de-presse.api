<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Request;
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
        $user = $this->getUser();
        $form = $this->getSettingsForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            /**
             * @var $entityManager \Doctrine\Orm\EntityManager
             */
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $entityManager->persist($user);
        }
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
