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
     *      name="weaving_the_web_dashboard_people_talking_about",
     *      requirements={"keywords": "[-,%\+a-zA-Z0-9]+"}
     * )
     * @Extra\Method({"GET"})
     *
     * @Extra\Cache(expires="+1 week", public="true")
     *
     * @param $keywords
     * @param \DateTime $since
     * @param \DateTime $until
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aggregateFilteredTermsAction($keywords, \DateTime $since, \DateTime $until)
    {
        $termsAggregationName = 'screen_name_aggregations';
        $filteredAggregationName = 'screen_name_aggregated_in_range';

        $timeSeries = [];
        $screenNamesAggregations = [];

        $keywords = explode(',', $keywords);

        $keywordIndex = 0;

        if (count($keywords) > 5) {
            $keywords = array_slice($keywords, 0, 5);
        }

        $fingerprint = sha1(serialize([$keywords, $since, $until]));
        $aggregationsPath = sprintf(__DIR__ . '/../Resources/json/aggregations/%s.json', $fingerprint);
        if (file_exists($aggregationsPath)) {
            $content = unserialize(base64_decode(file_get_contents($aggregationsPath)));

            return new JsonResponse($content);
        }

        $lastYear = $until->format('Y');

        foreach ($keywords as $keyword) {
            $match = new Query\Match();
            $match->setField('text', $keyword);

            $query = new Query($match);

            $aggregation = new Aggregation();

            $timeSeries[$keywordIndex] = [];

            foreach (range($since->format('Y'), $lastYear) as $year) {

                if ($year === $lastYear) {
                    $lastMonth = min(12, $until->format('m'));
                } else {
                    $lastMonth = 12;
                }

                foreach (range(1, $lastMonth) as $month) {

                    if ($year === $lastYear && $month == $lastMonth) {
                        $days = $until->format('d');
                    } else {
                        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    }

                    foreach (range(1, $days) as $day) {
                        $termsAggregation = $aggregation->terms($termsAggregationName);
                        $termsAggregation->setField('screenName');
                        $termsAggregation->setSize(30);

                        $yearMonthDay = $year . '-' .
                            str_pad($month, 2, '0', STR_PAD_LEFT). '-' .
                            str_pad($day, 2, '0', STR_PAD_LEFT);

                        $startDate = new \DateTime($yearMonthDay . ' 0:00') ;
                        $endDate = new \DateTime($yearMonthDay . ' 23:59');

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

                        foreach ($aggregations[$filteredAggregationName][$termsAggregationName]['buckets'] as $bucket) {
                            $screenNamesAggregations[$yearMonthDay][$keywordIndex][$bucket['key']] = $bucket['doc_count'];
                        }

                        $timeSeries[$keywordIndex][] = [
                            'date' => $yearMonthDay,
                            'mentions' => $aggregations[$filteredAggregationName]['doc_count']
                        ];
                    }
                }
            }

            $keywordIndex++;
        }

        $contents = [
            'time_series' => $timeSeries,
            'screen_name_aggregations' => $screenNamesAggregations
        ];

        file_put_contents($aggregationsPath, base64_encode(serialize($contents)));

        return new JsonResponse($contents);
    }
}