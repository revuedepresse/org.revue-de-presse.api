<?php

namespace WeavingTheWeb\Bundle\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use WTW\UserBundle\Entity\User;

class SettingsController extends ContainerAware
{
    public function saveAction()
    {
    }

    public function showAction()
    {
        $user = new User();

        /**
         * @var $formFactory \Symfony\Component\Routing\Router
         */
        $router = $this->container->get('router');

        /**
         * @var $formFactory \Symfony\Component\Form\FormFactory
         */
        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create('user', $user, [
            'method' => 'POST',
            'action' => $router->generate('weaving_the_web_user_save_settings')
        ]);

        /**
         * @var $templating \Symfony\Bundle\TwigBundle\TwigEngine
         */
        $templating = $this->container->get('templating');

        return $templating->renderResponse('WeavingTheWebUserBundle:Settings:show.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
