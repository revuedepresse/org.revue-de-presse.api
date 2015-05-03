<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

use Elastica\Filter\Range;

use Elastica\Query;

use Elastica\QueryBuilder\DSL\Aggregation;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AggregationController extends Controller
{
    /**
     * @Extra\Route(
     *      "/api/twitter/aggregate/{keywords}/{since}/{until}",
     *      name="weaving_the_web_dashboard_people_talking_about"
     * )
     * @Extra\Method({"GET"})
     *
     * @param $keywords
     * @param \DateTime $since
     * @param \DateTime $until
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAggregateFilteredTermsAction($keywords, \DateTime $since, \DateTime $until)
    {
        $match = new Query\Match();
        $match->setField('text', $keywords);

        $query = new Query($match);

        $aggregation = new Aggregation();

        $termsAggregation = $aggregation->terms('screen_name_aggregations');
        $termsAggregation->setField('screenName');
        $termsAggregation->setSize(30);

        $range = new Range('createdAt', [
            'lte' => $until->format('c'),
            'gte' => $since->format('c')
        ]);

        $filterName = 'screen_name_aggregated_in_range';
        $rangeFilterAggregation = $aggregation->filter($filterName, $range);

        $rangeFilterAggregation->addAggregation($termsAggregation);

        $query->addAggregation($rangeFilterAggregation);
        $query->setSize(100);

        $searchIndex = $this->container->getParameter('twitter_search_index');

        /** @var \FOS\ElasticaBundle\Elastica\Index $index */
        $index = $this->get('fos_elastica.index.' . $searchIndex);
        $userStatusType = $index->getType('user_status');

        $results = $userStatusType->search($query)->getAggregations();

        return new JsonResponse($results[$filterName]);
    }
}