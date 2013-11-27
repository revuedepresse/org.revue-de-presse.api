<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Routing;
use Symfony\Component\DependencyInjection\ContainerAwareInterface,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\HttpFoundation\RedirectResponse;
use WeavingTheWeb\Bundle\DashboardBundle\Entity\Search;

/**
 * Class SearchController
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 */
class SearchController implements ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container $container
     */
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @Routing\Route("/search", name="weaving_the_web_dashboard_search")
     * @Routing\Template("WeavingTheWebDashboardBundle:Search:search.html.twig")
     * @return array
     */
    public function searchAction()
    {
        $search = new Search();

        /**
         * @var \Symfony\Component\Form\FormFactory $formFactory
         */
        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create('elasticsearch', $search, [
            'action' => $this->container->get('router')->generate('weaving_the_web_dashboard_search'),
            'method' => 'POST'
        ]);

        if ($this->container->get('request')->isMethod('POST')) {
            return new RedirectResponse($this->container->get('router')->generate('wtw_registration_land'));
        }

        return ['form' => $form->createView()];
    }
}