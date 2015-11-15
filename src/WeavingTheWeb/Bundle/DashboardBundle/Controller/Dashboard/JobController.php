<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\DashboardBundle\Controller\AbstractController;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/dashboard/job", service="weaving_the_web_dashboard.controller.job")
 */
class JobController extends AbstractController
{
    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\RemoteRepository
     */
    public $remoteRepository;
    /**
     * @return array
     *
     * @Extra\Route(
     *      "/",
     *      name="weaving_the_web_dashboard_job_show_jobs"
     * )
     * @Extra\Template("WeavingTheWebDashboardBundle:Dashboard/Job:_list.html.twig")
     */
    public function showJobsAction()
    {
        $remote = $this->remoteRepository->findOneBy([
            'user' => $this->getUser(),
            'selected' => true
        ]);

        return [
            'active_menu_item' => 'dashboard_jobs',
            'remote' => $remote
        ];
    }
}
