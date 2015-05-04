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
        $termsAggregationName = 'screen_name_aggregations';
        $filteredAggregationName = 'screen_name_aggregated_in_range';

        $results = [];

        $keywords = explode(',', $keywords);

        $i = 0;

        foreach ($keywords as $keyword) {
            $match = new Query\Match();
            $match->setField('text', $keyword);

            $query = new Query($match);

            $aggregation = new Aggregation();

            $results[$i] = [];

            foreach (range($since->format('Y'), $until->format('Y')) as $year) {

                foreach (range(1, 12) as $month) {
                    $termsAggregation = $aggregation->terms($termsAggregationName);
                    $termsAggregation->setField('screenName');
                    $termsAggregation->setSize(30);

                    $startYear = $year;

                    if ($month === 12) {
                        $endYear = $year + 1;
                        $endMonth = 1;
                    } else {
                        $endYear = $year;
                        $endMonth = $month + 1;
                    }


                    $month = str_pad($month, 2, STR_PAD_LEFT, '0');
                    $endMonth = str_pad($endMonth, 2, STR_PAD_LEFT, '0');

                    $startDate = new \DateTime($startYear . '-' . $month . '-01') ;
                    $endDate = new \DateTime($endYear . '-' . $endMonth . '-01');

                    $range = new Range('createdAt', [
                        'gte' => $startDate->format('c'),
                        'lte' => $endDate->format('c')
                    ]);

                    $rangeFilterAggregation = $aggregation->filter($filteredAggregationName, $range);

                    $rangeFilterAggregation->addAggregation($termsAggregation);

                    $query->addAggregation($rangeFilterAggregation);
                    $query->setSize(100);

                    $searchIndex = $this->container->getParameter('twitter_search_index');

                    /** @var \FOS\ElasticaBundle\Elastica\Index $index */
                    $index = $this->get('fos_elastica.index.' . $searchIndex);
                    $userStatusType = $index->getType('user_status');

                    $aggregations = $userStatusType->search($query)->getAggregations();

                    $results[$i][] = [
                        'date' => $year . '-' . $endMonth . '-01',
                        'mentions' => $aggregations[$filteredAggregationName]['doc_count']
                    ];
                }
            }

            $i++;
        }

        return new JsonResponse($results);
    }
}