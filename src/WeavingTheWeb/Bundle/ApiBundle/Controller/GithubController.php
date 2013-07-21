<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest,
    FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * Class GithubController
 *
 * @package WeavingTheWeb\Bundle\ApiBundle\Controller
 *
 * @Rest\NamePrefix("weaving_the_web_api_")
 */
class GithubController extends ResourceController
{
    /**
     * Get repositories
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @Rest\QueryParam(name="page", requirements="\d+", default="1", description="page")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="50", description="limit")
     *
     * @Cache(expires="tomorrow")
     *
     * @return array
     */
    public function getRepositoriesAction(ParamFetcherInterface $paramFetcher)
    {
        $dataProvider = $this->get('weaving_the_web.api.data_provider');
        $parameters = $this->validatesParameters($paramFetcher);

        $jsonResources = $dataProvider->getByConstraints(
            'WeavingTheWebApiBundle:GithubRepository',
            array(
                'sorting_columns' => array('DESC' => 'id'),
                'grouping_columns' => array('cloneUrl'),
                'limit' => (int)$parameters['limit'],
                'offset' => (int)$parameters['offset']));
        $classifiedResources = $this->classifyResources($jsonResources);

        return array(
            'data_type' => 'Github Repositories',
            'data' => $classifiedResources);
    }
}
