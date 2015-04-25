<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest,
    FOS\RestBundle\Request\ParamFetcherInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * @Rest\NamePrefix("weaving_the_web_api_")
 */
class TwitterController extends ResourceController
{
    /**
     * Get stream items
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
    public function getUsersStreamsAction(ParamFetcherInterface $paramFetcher)
    {
        $dataProvider = $this->get('weaving_the_web.api.data_provider');
        $parameters = $this->validatesParameters($paramFetcher);

        $jsonResources = $dataProvider->getByConstraints(
            'WeavingTheWebApiBundle:Json',
            array(
                'type' => 2,
                'offset' => (int) $parameters['offset'],
                'limit' => (int) $parameters['limit']));
        $resources = $this->classifyResources($jsonResources);
        $target = 'value';
        $expander = function (&$properties) use ($target) {
            return $this->expandProperty($properties, $target);
        };
        array_walk($resources, $expander);

        return array(
            'data_type' => 'Twitter Users Streams',
            'data' => $resources);
    }

    public function expandProperty(&$properties, $name)
    {
        if (false !== array_key_exists($name, $properties)) {
            $properties[$name] = json_decode($properties[$name], true);
        }

        return $properties;
    }
}
