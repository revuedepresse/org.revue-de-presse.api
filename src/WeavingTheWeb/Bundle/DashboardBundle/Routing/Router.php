<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Routing;

/**
 * Class Sitemap
 * @package WeavingTheWeb\Bundle\DashboardBundle\Routing
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Router
{
    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var \Symfony\Component\Routing\Router $router
     */
    protected $router;

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @param $criteria
     * @return object
     */
    public function getPerspectives($columns, $conditions, $parameters)
    {
        /**
         * @var $perspectiveRepository \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository
         */
        $perspectiveRepository = $this->entityManager->getRepository('\WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective');

        return $perspectiveRepository->getIterablePerspectives($columns, $conditions, $parameters);
    }

    /**
     * @param $hash
     * @return string
     */
    public function generatePerspectiveUrl($perspective)
    {
        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective $perspective
         */
        $hash = $perspective->getHash();

        return $this->router->generate('weaving_the_web_dashboard_show_perspective', ['hash' => $hash]);
    }

    /**
     * @return object
     */
    public function getPublicPerspectives()
    {
        $publicStatus = \WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective::STATUS_PUBLIC;

        return $this->getPerspectives(['{alias}.hash', '{alias}.name', ],
            ['{alias}.status = :status'], ['status' => $publicStatus]);
    }

    /**
     * @return object
     */
    public function getPublicPerspectivesSitemap()
    {
        $sitemap = [];

        $perspectives = $this->getPublicPerspectives();
        foreach ($perspectives AS $collection) {
            foreach ($collection as $perspective) {
                $hash = $perspective['hash'];
                $partialHash = substr($hash, 0, 7);
                $sitemap[$perspective['name']] = $this->router->generate('weaving_the_web_dashboard_show_perspective', ['hash' => $partialHash]);
            }
        }

        return $sitemap;
    }
}