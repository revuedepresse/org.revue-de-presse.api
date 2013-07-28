<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class GithubController
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @Extra\Route("/github")
 */
class GithubController extends Controller
{
    /**
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
