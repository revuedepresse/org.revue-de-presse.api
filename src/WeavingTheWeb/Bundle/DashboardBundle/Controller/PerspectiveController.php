<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Doctrine\Orm\NoResultException;

/**
 * Class PerspectiveController
 *
 * @package WeavingTheWeb\Bundle\DashboardBundle\Controller
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route("/perspective")
 */
class PerspectiveController extends ContainerAware
{
    /**
     * @Extra\Cache(expires="+2 hours")
     * @Extra\Route("/{hash}", name="weaving_the_web_dashboard_show_perspective")
     */
    public function showPerspectiveAction($hash)
    {
        /**
         * @var \Doctrine\Orm\EntityManager $entityManager
         */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository $perspectiveRepository
         */
        $perspectiveRepository = $entityManager->getRepository('WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective');

        try {
            /**
             * @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
             */
            $perspective = $perspectiveRepository->findOneByPartialHash($hash);
            $sql = $perspective->getValue();
        } catch (NoResultException $exception) {
            throw new NotFoundHttpException('The requested query it not available', $exception);
        }

        if ($this->isQueryValid($sql) && ($perspective->getStatus() === $perspective::STATUS_PUBLIC)) {
            /**
              * @var $connection \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection
              */
             $connection = $this->container->get('weaving_the_web_dashboard.dbal_connection');
             $query = $connection->executeQuery($sql);
             $translator = $this->container->get('translator');

             /**
              * @var \Symfony\Bundle\TwigBundle\TwigEngine $templateEngine
              */
            $templateEngine = $this->container->get('templating');

            if ($perspective->getName() !== null) {
                $perspectiveTitle = $perspective->getName();
            } else {
                $perspectiveTitle = $translator->trans('title_perspective');
            }

            return $templateEngine->renderResponse(
                 'WeavingTheWebDashboardBundle:Perspective:showPerspective.html.twig', array(
                     'active_menu_item' => 'dashboard',
                     'error' => $query->error,
                     'default_query' => $query->sql,
                     'records' => $query->records,
                     'title' => $perspectiveTitle
                )
            );
        } else {
            throw new NotFoundHttpException('The requested query it not available');
        }
    }

    /**
     * @param $sql
     */
    public function isQueryValid($sql)
    {
        $loweredQuery = strtolower($sql);

        return false === strpos($loweredQuery, 'delete') &&
            false === strpos($loweredQuery, 'update') &&
            false === strpos($loweredQuery, 'grant') &&
            false === strpos($loweredQuery, 'create');
    }
} 