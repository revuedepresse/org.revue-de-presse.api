<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Handles responses containing information about GitHub repositories
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @Extra\Route("/github")
 */
class GithubController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @Extra\Route("/repositories", name="weaving_the_web_dashboard_show_repositories")
     * @Secure("ROLE_USER")
     */
    public function showRepositoriesAction()
    {
        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        return $this->render(
            'WeavingTheWebDashboardBundle:Github:showRepositories.html.twig', array(
                'active_menu_item' => 'github_repositories',
                'title'            => 'Github Starred Repositories'
            ));
    }
}
