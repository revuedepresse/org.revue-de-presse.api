<?php

namespace WTW\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

/**
 * Class GithubController
 *
 * @package WTW\DashboardBundle\Controller
 * @Extra\Route("/github")
 */
class GithubController extends Controller
{
    /**
     * @Extra\Route("/repositories", name="dashboard_show_repositories")
     */
    public function showRepositoriesAction()
    {
        return $this->render(
            'WTWDashboardBundle:Github:showRepositories.html.twig', array(
                'title' => 'Github Starred Repositories'
            ));
    }
}
