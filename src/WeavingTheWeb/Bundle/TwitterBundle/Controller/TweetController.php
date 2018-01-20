<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Controller
 */
class TweetController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     *
     * @Extra\Route("/tweet/latest", name="weaving_the_web_twitter_tweet_latest")
     * @Extra\Method({"GET", "OPTIONS"})
     */
    public function latestAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        } else {
            try {
                $oauthTokens = $this->parseOAuthTokens($request);

                /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository */
                $userStreamRepository = $this->get('weaving_the_web_twitter.repository.read.user_stream');
                $userStreamRepository->setOauthTokens($oauthTokens);

                $lastId = $request->get('lastId', null);
                $statuses = $userStreamRepository->findLatest($lastId);
                $statusCode = 200;

                return new JsonResponse($statuses, $statusCode, $this->getAccessControlOriginHeaders());
            } catch (\PDOException $exception) {
                return $this->getExceptionResponse(
                    $exception,
                    $this->get('translator')->trans('twitter.error.database_connection', [], 'messages')
                );
            } catch (\Exception $exception) {
                return $this->getExceptionResponse($exception);
            }
        }
    }

    /**
     * @param \Exception $exception
     * @param null $message
     * @return JsonResponse
     */
    protected function getExceptionResponse(\Exception $exception, $message = null)
    {
        if (is_null($message)) {
            $data = ['error' => $exception->getMessage()];
        } else {
            $data = ['error' => $message];
        }
        $statusCode = 500;

        return new JsonResponse($data, $statusCode, $this->getAccessControlOriginHeaders());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     *
     * @Extra\Route("/bookmarks", name="weaving_the_web_twitter_tweet_sync_bookmarks")
     * @Extra\Method({"POST", "OPTIONS"})
     */
    public function syncBookmarksAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        } else {
            try {
                $oauthTokens = $this->parseOAuthTokens($request);

                /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository */
                $userStreamRepository = $this->get('weaving_the_web_twitter.repository.read.user_stream');
                $userStreamRepository->setOauthTokens($oauthTokens);

                $statusIds = $request->get('statusIds', array());
                $statuses = $userStreamRepository->findBookmarks($statusIds);

                // TODO Mark statuses as starred before returning them

                $statusCode = 200;

                return new JsonResponse($statuses, $statusCode, $this->getAccessControlOriginHeaders());
            } catch (\Exception $exception) {
                return $this->getExceptionResponse($exception);
            }
        }
    }

    /**
     * @return array
     */
    protected function getAccessControlOriginHeaders()
    {
        return ['Access-Control-Allow-Origin' => '*'];
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    protected function parseOAuthTokens(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $username = $request->get('username', null);

        if (is_null($username)) {
            $oauthToken = $request->get(
                'token',
                null,
                $this->container->getParameter('weaving_the_web_twitter.oauth_token.default')
            );

            if ($oauthToken !== null) {
                $oauthTokens = [$oauthToken];
            } else {
                throw new \Exception($this->get('translator')->trans('twitter.error.invalid_oauth_token', [], 'messages'));
            }
        } else {
            /** @var \WTW\UserBundle\Entity\User $user */
            $user = $userManager->findUserBy(['twitter_username' => $username]);

            if (is_null($user)) {
                throw new \Exception(sprintf(
                    'No user can be found for username "%s"',
                    $username
                ));
            }

            $tokens = $user->getTokens()->toArray();

            $oauthTokens = [];
            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token */
            foreach ($tokens as $token) {
                $oauthToken = $token->getOauthToken();
                $oauthTokens[] = $token->getOauthToken();
                if (strlen(trim($oauthToken)) === 0) {
                    throw new \Exception(sprintf(
                        'Invalid token for username "%s"',
                        $username
                    ));
                }
            }
        }

        return $oauthTokens;
    }

    /**
     * @return JsonResponse
     */
    protected function getCorsOptionsResponse()
    {
        return new JsonResponse(
            [],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => implode(
                    ', ',
                    [
                        'Authorization',
                        'Keep-Alive',
                        'User-Agent',
                        'X-Requested-With',
                        'If-Modified-Since',
                        'Cache-Control',
                        'Content-Type'
                    ]
                )
            ]
        );
    }

    /**
     * @Extra\Route("/tweet/star/{statusId}", name="weaving_the_web_twitter_tweet_star")
     * @Extra\Method({"POST", "OPTIONS"})
     * @Extra\ParamConverter(
     *      "userStream",
     *      class="WeavingTheWebApiBundle:UserStream",
     *      options={"entity_manager"="write"}
     * )
     *
     * @param UserStream $userStream
     * @return JsonResponse
     */
    public function starAction(UserStream $userStream)
    {
        return $this->toggleStarringStatus($userStream, $starring = true);
    }

    /**
     * @Extra\Route("/tweet/unstar/{statusId}", name="weaving_the_web_twitter_tweet_unstar")
     * @Extra\Method({"POST", "OPTIONS"})
     * @Extra\ParamConverter(
     *      "userStream",
     *      class="WeavingTheWebApiBundle:UserStream",
     *      options={"entity_manager"="write"}
     * )
     *
     * @param UserStream $userStream
     * @return JsonResponse
     */
    public function unstarAction(UserStream $userStream)
    {
        return $this->toggleStarringStatus($userStream, $starring = false);
    }

    /**
     * @Extra\Route("/list/{handle}", name="weaving_the_web_twitter_list_tweets")
     *
     * @param Request $request
     * @param $handle
     * @return JsonResponse
     *
     * @return JsonResponse
     */
    public function listTweetsAction(Request $request, $handle)
    {
        $query = '
            SELECT ust_text as text, ust_status_id as Id, ust_created_at as CreationDate,
            ust_api_document api_document,
            CONCAT("https://twitter.com/'.$handle.'/status/", us.ust_status_id) as link
            FROM weaving_twitter_user_stream us
            LEFT JOIN weaving_status_aggregate sa ON us.ust_id = sa.status_id
            LEFT JOIN weaving_aggregate a ON a.id = sa.aggregate_id
            WHERE a.name like "%'.$handle.'%"
            ORDER BY ust_created_at DESC
            LIMIT 100
        ';

        /** @var \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection $connection */
        $connection = $this->container->get('weaving_the_web_dashboard.dbal_connection.read');
        $results = $connection->executeQuery($query, [], 'read');

        if (
            !is_object($results) ||
            !in_array('records', array_keys(get_object_vars($results))) ||
            !is_array($results->records)
        ) {
            return new JsonResponse([]);
        }

        $results->records = array_map(function ($row) {
            $decodedDocument = json_decode($row['api_document'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            unset($row['api_document']);
            $row['text'] = $decodedDocument['text'];

            return array_merge(
                $row,
                [
                    'favorites' => $this->countFavourites($decodedDocument),
                    'retweets' => $this->countRetweets($decodedDocument),
                ]
            );
        }, $results->records);

        $results->records = $this->sortStatuses($request, $results->records);
        $results->records = $this->filterStatuses($request, $results->records);

        return new JsonResponse($results);
    }

    /**
     * @Extra\Route("/show-tweet/{tweetId}", name="weaving_the_web_twitter_show_tweet")
     *
     * @param string $tweetId
     *
     * @return JsonResponse
     */
    public function showTweetAction($tweetId)
    {
        $accessor = $this->get('weaving_the_web_twitter.api_accessor');

        return new JsonResponse($accessor->getTweet($tweetId));
    }

    /**
     * @Extra\Route("/show-aggregate-stream", name="weaving_the_web_twitter_list_aggregates")
     *
     * @return JsonResponse
     */
    public function showAggregatesAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        }

        $query = '
            SELECT a.name
            FROM weaving_aggregate a
            ORDER BY name
        ';

        /** @var \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection $connection */
        $connection = $this->container->get('weaving_the_web_dashboard.dbal_connection.read');
        $results = $connection->executeQuery($query, [], 'read');

        $response = new JsonResponse(
            $results->records,
            200,
            ['Access-Control-Allow-Origin' => '*']
        );

        $twoDays = 3600 * 24 * 2;
        $response->setSharedMaxAge($twoDays);

        $date = new \DateTime();
        $date->modify('+'.$twoDays.' seconds');

        $response->setExpires($date);
        $response->setPublic();

        return $response;;
    }

    /**
     * @Extra\Route("/show-user/{identifier}", name="weaving_the_web_twitter_show_user")
     *
     * @param $identifier
     * @return JsonResponse
     */
    public function showUserAction($identifier)
    {
        $accessor = $this->get('weaving_the_web_twitter.api_accessor');

        return new JsonResponse($accessor->showUser($identifier));
    }

    /**
     * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline.html
     *
     * @Extra\Route("/show-user-stream/{identifier}", name="weaving_the_web_twitter_show_user_stream")
     *
     * @param Request $request
     * @param $identifier
     * @return JsonResponse
     */
    public function showUserStreamAction(Request $request, $identifier = null)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        }

        if (is_null($identifier)) {
            $identifier = 'thierrymarianne';
        }

        $accessor = $this->get('weaving_the_web_twitter.api_accessor');

        $shouldTrimUser = false;
        if ($request->query->has('trim_user')) {
            $shouldTrimUser = $request->query->get('trim_user');
        }

        $userTimeline = $accessor->fetchTimelineStatuses([
            'screen_name' => $identifier,
            'include_rts' => true,
            'exclude_replies' => false,
            'count' => 200,
            'trim_user' => $shouldTrimUser
        ]);
        array_walk($userTimeline, function ($status, $index) use (&$userTimeline) {
            $status->user = $status->user->screen_name;
            $userTimeline[$index] = $status;
        });
        $userTimeline = $this->sortStatuses($request, $userTimeline);
        $userTimeline = $this->filterStatuses($request, $userTimeline);

        $response = new JsonResponse(
            $userTimeline,
            200,
            ['Access-Control-Allow-Origin' => '*']
        );

        $twoDays = 3600 * 24 * 2;
        $response->setSharedMaxAge($twoDays);

        $date = new \DateTime();
        $date->modify('+'.$twoDays.' seconds');

        $response->setExpires($date);
        $response->setPublic();

        return $response;
    }

    /**
     * @Extra\Route("/show-home-stream", name="weaving_the_web_twitter_show_home_stream")
     *
     * @return JsonResponse
     *
     * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-home_timeline
     */
    public function showHomeStreamAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        }

        $accessor = $this->get('weaving_the_web_twitter.api_accessor');

        $shouldTrimUser = false;
        if ($request->query->has('trim_user')) {
            $shouldTrimUser = $request->query->get('trim_user');
        }

        $statuses = (array) $accessor->fetchHomeTimelineStatuses([
            'exclude_replies' => false,
            'count' => 800,
            'trim_user' => $shouldTrimUser
        ]);

        array_walk($statuses, function ($status, $index) use (&$statuses) {
            $status = (array) $status;
            $user = (array) $status['user'];

            $screenName = '';
            if (array_key_exists('screen_name', $user)) {
                $screenName = $user['screen_name'];
            }

            $statuses[$index] = [
                'text' => $status['text'],
                'user' => $screenName,
                'created_at' => $status['created_at'],
                'retweet_count' => $status['retweet_count'],
                'favorite_count' => $status['favorite_count'],
            ];
        });
        $statuses = $this->sortStatuses($request, $statuses);
        $statuses = $this->filterStatuses($request, $statuses);

        $response = new JsonResponse(
            $statuses,
            200,
            ['Access-Control-Allow-Origin' => '*']
        );

        $twoDays = 3600 * 24 * 2;
        $response->setSharedMaxAge($twoDays);

        $date = new \DateTime();
        $date->modify('+'.$twoDays.' seconds');

        $response->setExpires($date);
        $response->setPublic();

        return $response;
    }

    /**
     * @param $decodedDocument
     * @return int
     */
    private function countFavourites($decodedDocument)
    {
        if (array_key_exists('favorite_count', $decodedDocument)) {
            return $decodedDocument['favorite_count'];
        }

        return 0;
    }

    /**
     * @param $decodedDocument
     * @return int
     */
    private function countRetweets($decodedDocument)
    {
        if (array_key_exists('retweet_count', $decodedDocument)) {
            return $decodedDocument['retweet_count'];
        }

        return 0;
    }

    /**
     * @param UserStream $userStream
     * @param bool $starred
     * @return JsonResponse
     */
    protected function toggleStarringStatus(UserStream $userStream, $starred = false)
    {
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getMasterRequest();

        if ($request->isMethod('POST')) {
            $userStream->setStarred($starred);

            $clonedUserStream = clone $userStream;
            $clonedUserStream->setUpdatedAt(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager('write');

            $entityManager->remove($userStream);
            $entityManager->flush();

            $entityManager->persist($clonedUserStream);
            $entityManager->flush();

            return new JsonResponse([
                'status' => $userStream->getStatusId()
            ], 200, ['Access-Control-Allow-Origin' => '*']);
        } else {
            return $this->getCorsOptionsResponse();
        }
    }

    /**
     * @param Request $request
     * @param $statuses
     * @return mixed
     */
    private function sortStatuses(Request $request, $statuses)
    {
        if ($request->query->has('sort_by')) {
            usort($statuses, function ($a, $b) use ($request) {
                $criteria = $request->query->get('sort_by');

                if (!is_array($a)) {
                    $a = (array) $a;
                    $b = (array) $b;
                }

                if ($a[$criteria] > $b[$criteria]) {
                    return -1;
                }

                if ($a[$criteria] < $b[$criteria]) {
                    return 1;
                }

                return 0;
            });
        }

        return $statuses;
    }

    /**
     * @param Request $request
     * @param array   $records
     * @return array
     */
    private function filterStatuses(Request $request, $records)
    {
        if ($request->query->has('contains')) {
            return array_filter($records, function ($row) use ($request) {
                $term = $request->query->get('contains');

                if (false !== strpos(strtolower($row['text']), $term)) {
                    return true;
                }

                return false;
            });
        }

        return $records;
    }
}
