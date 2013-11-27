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
        return $this->showSearchFormAction();
    }

    /**
     * @Routing\Route("/search", name="weaving_the_web_dashboard_show_search_form")
     * @Routing\Template("WeavingTheWebDashboardBundle:Search:searchForm.html.twig")
     * @return array
     */
    public function showSearchFormAction()
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
        $parameters = [
            'form' => $form->createView(),
            'title' => $this->container->get('translator')->trans('title', [], 'search'),
        ];

        /**
         * @var \Symfony\Component\HttpFoundation\Request $request
         */
        $request = $this->container->get('request');
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            /**
             * @var \FOS\ElasticaBundle\Finder\TransformedFinder $finder
             */
            $finder = $this->container->get('fos_elastica.finder.twitter.user_status');
            $keywords = $form->get('keywords')->getData();
            $matches = $finder->find($keywords, 100);
            $parameters['matches'] = $matches;

            return $parameters;
        }

        return $parameters;
    }
}