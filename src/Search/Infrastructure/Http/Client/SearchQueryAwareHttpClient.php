<?php
declare (strict_types=1);

namespace App\Search\Infrastructure\Http\Client;

use App\Search\Domain\Entity\SavedSearch;
use App\Search\Domain\SearchQueryAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\TwitterAPIEndpointsAwareInterface;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\Client\HttpSearchParamReducerTrait;
use Exception;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;
use App\Search\Domain\SavedSearchAwareInterface;
use App\Search\Infrastructure\DependencyInjection\SavedSearchTrait;

class SearchQueryAwareHttpClient implements
    SearchQueryAwareHttpClientInterface,
    TwitterAPIEndpointsAwareInterface,
    SavedSearchAwareInterface
{
    use HttpClientTrait;
    use HttpSearchParamReducerTrait;
    use LoggerTrait;
    use SavedSearchTrait;

    private function params(string $queryString): array
    {
        return $this->reduceParameters($queryString, []);
    }

    private function saveSearch(string $query): \stdClass|array
    {
        $endpoint = $this->getCreateSavedSearchEndpoint() . "query=$query";

        return $this->httpClient->contactEndpoint($endpoint);
    }

    /**
     * @throws DatetimeException
     */
    public function searchTweets(string $searchQuery)
    {
        $savedSearch = $this->savedSearchRepository
            ->findOneBy(['searchQuery' => $searchQuery]);

        if (!($savedSearch instanceof SavedSearch)) {
            $response = $this->saveSearch($searchQuery);
            $savedSearch = $this->savedSearchRepository->make($response);
            $this->savedSearchRepository->save($savedSearch);
        }

        $this->iterateOverLastPeriodAvailableByUsingTwitterAPI($savedSearch);
    }

    private function getCreateSavedSearchEndpoint($version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->httpClient->getApiBaseUrl($version) . self::API_ENDPOINT_CREATE_SAVED_SEARCHES . '?';
    }

    private function getSearchEndpoint($version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->httpClient->getApiBaseUrl($version) . self::API_ENDPOINT_SEARCH_TWEETS . '?';
    }

    private function hasOneKeyOfTypeStringAtLeast(array $params): bool
    {
        return count(array_filter(array_keys($params), 'is_string')) > 0;
    }

    /**
     * See [Search Tweets: Standard v1.1](https://developer.twitter.com/en/docs/twitter-api/v1/tweets/search/api-reference/get-search-tweets)
     *
     * @throws DatetimeException
     * @throws Exception
     */
    private function iterateOverLastPeriodAvailableByUsingTwitterAPI(SavedSearch $savedSearch): void
    {
        $howManyDaysAgo = 8;
        $params = [];
        $today = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $breakLoop = fn ($howManyDaysAgo) => $howManyDaysAgo >= 0;

        do {
            $params['until'] = $today
                ->modify('- '.$howManyDaysAgo . ' days')
                ->format('Y-m-d');

            $results = $this->search(
                $savedSearch->searchQuery,
                $params
            );

            $nextResultsParams = (string)((array)((array)$results)['search_metadata'])['next_results'];

            if (count(((array)$results)['statuses']) === 0) {
                $howManyDaysAgo--;

                if ($breakLoop($howManyDaysAgo)) {
                    break;
                }

                continue;
            }

            $params = $this->params($nextResultsParams);
            unset($params['q'], $params['count']);

            $totalRecordedTweets = $this->searchMatchingTweetRepository->saveSearchMatchingStatus(
                $savedSearch,
                $results->statuses,
                $this->httpClient->userToken
            );
            $this->logger->info(
                'Recorded search query-based tweets',
                ['total_records' => $totalRecordedTweets]
            );

            if ($totalRecordedTweets > 0) {
                $this->iterateOverCursors($savedSearch, $params);
            }

            $howManyDaysAgo--;
        } while ($breakLoop($howManyDaysAgo));
    }

    private function iterateOverCursors(SavedSearch $savedSearch, array $params): void
    {
        do {
            $results = $this->search(
                $savedSearch->searchQuery,
                $params
            );

            $nextResultsParams = ((array)((array)$results)['search_metadata'])['next_results'];

            if (count(((array)$results)['statuses']) === 0) {
                break;
            }

            $params = $this->params($nextResultsParams);
            unset($params['q'], $params['count']);

            $totalRecordedTweets = $this->searchMatchingTweetRepository->saveSearchMatchingStatus(
                $savedSearch,
                $results->statuses,
                $this->httpClient->userToken
            );

            $this->logger->info(
                'Recorded search query-based tweets',
                ['total_records' => $totalRecordedTweets]
            );
        } while ($totalRecordedTweets > 0);
    }

    private function search(string $query, array $params = [])
    {
        $queryStringSuffix = '';

        if ($this->hasOneKeyOfTypeStringAtLeast($params)) {
            $values = array_values($params);
            $keys = array_keys($params);

            $paramsAsString = array_map(
                fn ($value, $key) => trim($key).'='.trim($value),
                $values,
                $keys
            );

            $queryStringSuffix = '&' . implode('&', $paramsAsString);
        } elseif (count($params) > 0) {
            $queryStringSuffix = '&' . implode('&', $params);
        }

        $queryParam = strtr(
            $query,
            ["'" => '']
        );

        $endpoint = $this->getSearchEndpoint() .
            'q='.$queryParam .
            '&count=100' .
            '&include_entities=true' .
            '&result_type=mixed' .
            $queryStringSuffix
        ;

        return $this->httpClient->contactEndpoint($endpoint);
    }
}
