<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Controller;

use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\StatusAccessor;
use Doctrine\DBAL\Exception\ConnectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Controller
 */
class TweetController extends Controller
{
    /** @var StatusRepository */
    private $statusRepository;

    /**
     * @var StatusAccessor
     */
    private $statusAccessor;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     *
     * @Extra\Route("/tweet/latest", name="weaving_the_web_twitter_tweet_latest")
     *
     * @Extra\Method({"GET", "OPTIONS"})
     *
     * @Extra\Cache(public=true)
     */
    public function getStatusesAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        }

        try {
            $this->statusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
            // Look for statuses collected by any given access token
            // (there is no restriction at this point of the implementation)
            $this->statusRepository->setOauthTokens([]);

            $lastId = $request->get('lastId', null);
            $aggregateName = $request->attributes->get('aggregate_name', null);

            $rawSql = false;

            if (!is_null($aggregateName)) {
                $aggregateName = str_replace('___', ' ', $aggregateName);
                $aggregateName = str_replace('__', ' :: ', $aggregateName);
                $aggregateName = str_replace('_', ' _ ', $aggregateName);
                $rawSql = true;
            }

            $statuses = $this->statusRepository->findLatest($lastId, $aggregateName, $rawSql);
            $statusCode = 200;

            $statuses = $this->extractStatusProperties($statuses, $includeRepliedToStatuses = false);

            $response = new JsonResponse(
                $statuses,
                $statusCode,
                $this->getAccessControlOriginHeaders()
            );

            $encodedStatuses = json_encode($statuses);
            $this->setContentLengthHeader($response, $encodedStatuses);

            return $this->setCacheHeaders($response);
        } catch (\PDOException $exception) {
            return $this->getExceptionResponse(
                $exception,
                $this->get('translator')->trans('twitter.error.database_connection', [], 'messages')
            );
        } catch (ConnectionException $exception) {
            $this->get('logger')->critical('Could not connect to the database');
        } catch (\Exception $exception) {
            return $this->getExceptionResponse($exception);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     *
     * @Extra\Route(
     *     "/status/{id}",
     *     name="weaving_the_web_twitter_status",
     *     requirements={"id"="\S+"}
     * )
     *
     * @Extra\Method({"GET", "OPTIONS"})
     *
     * @Extra\Cache(public=true)
     */
    public function getStatusAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse();
        }

        try {
            $this->statusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
            $statusId = $request->attributes->get('id');
            $status = $this->statusRepository->findStatusIdentifiedBy($statusId);
            $statusCode = 200;

            $statuses = [$status];
            if (is_null($status)) {
                $statuses = [];
            }

            $statuses = $this->extractStatusProperties($statuses, $includeRepliedToStatuses = true);

            $response = new JsonResponse(
                $statuses,
                $statusCode,
                $this->getAccessControlOriginHeaders()
            );

            $encodedStatuses = json_encode($statuses);
            $this->setContentLengthHeader($response, $encodedStatuses);
            $this->setCacheHeaders($response);

            return $response;
        } catch (\PDOException $exception) {
            return $this->getExceptionResponse(
                $exception,
                $this->get('translator')->trans('twitter.error.database_connection', [], 'messages')
            );
        } catch (ConnectionException $exception) {
            $this->get('logger')->critical('Could not connect to the database');
        } catch (NotFoundStatusException $exception) {
            $errorMessage = sprintf("Could not find status with id '%s'", $statusId);
            $this->get('logger')->info($errorMessage);

            return $this->setCacheHeaders(new JsonResponse(
                ['error' => $errorMessage],
                404,
                $this->getAccessControlOriginHeaders()
            ));
        } catch (\Exception $exception) {
            return $this->getExceptionResponse($exception);
        }
    }

    /**
     * @param array $status
     * @param array $decodedDocument
     * @param bool  $includeRepliedToStatuses
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function updateFromDecodedDocument(
        array $status,
        array $decodedDocument,
        bool $includeRepliedToStatuses = false
    ): array {
        $status['media'] = [];
        if (array_key_exists('entities', $decodedDocument) &&
            array_key_exists('media', $decodedDocument['entities'])
        ) {
            $status['media'] = array_map(
                function ($media) {
                    if (array_key_exists('media_url_https', $media)) {
                        return [
                            'sizes' => $media['sizes'],
                            'url' => $media['media_url_https'],
                        ];
                    }
                },
                $decodedDocument['entities']['media']
            );
        }

        if (array_key_exists('avatar_url', $decodedDocument)) {
            $status['avatar_url'] = $decodedDocument['avatar_url'];
        }

        if (array_key_exists('user', $decodedDocument) &&
            array_key_exists('profile_image_url_https', $decodedDocument['user'])) {
            $status['avatar_url'] = $decodedDocument['user']['profile_image_url_https'];
        }

        if (array_key_exists('retweet_count', $decodedDocument)) {
            $status['retweet_count'] = $decodedDocument['retweet_count'];
        }

        if (array_key_exists('favorite_count', $decodedDocument)) {
            $status['favorite_count'] = $decodedDocument['favorite_count'];
        }

        if (array_key_exists('created_at', $decodedDocument)) {
            $status['published_at'] = $decodedDocument['created_at'];
        }

        return $this->extractConversationProperties(
            $status,
            $decodedDocument,
            $includeRepliedToStatuses
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     *
     * @Extra\Route(
     *     "/tweet/latest/{aggregate_name}",
     *     name="weaving_the_web_twitter_tweet_latest_for_aggregate",
     *     requirements={"aggregate_name"="\S+"}
     * )
     *
     * @Extra\Method({"GET", "OPTIONS"})
     *
     * @Extra\Cache(public=true)
     */
    public function getLatestStatusesForAggregate(Request $request)
    {
        return $this->getStatusesAction($request);
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

        $this->get('logger')->critical($data['error']);

        $statusCode = 500;

        return new JsonResponse($data, $statusCode, $this->getAccessControlOriginHeaders());
    }

    /**
     * @return array
     */
    protected function getAccessControlOriginHeaders()
    {
        if ($this->get('service_container')->getParameter('kernel.environment') === 'prod') {
            $allowedOrigin = $this->get('service_container')->getParameter('allowed.origin');

            return ['Access-Control-Allow-Origin' => $allowedOrigin];
        }

        return ['Access-Control-Allow-Origin' => '*'];
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    protected function parseOAuthTokens(Request $request)
    {
        $userManager = $this->get('user_manager');
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
            $user = $userManager->findOneBy(['twitter_username' => $username]);

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
        $allowedHeaders = implode(
            ', ',
            [
                'Keep-Alive',
                'User-Agent',
                'X-Requested-With',
                'If-Modified-Since',
                'Cache-Control',
                'Content-Type',
                'x-auth-token',
                'x-decompressed-content-length'
            ]
        );

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => $allowedHeaders,
        ];
        if ($this->get('service_container')->getParameter('kernel.environment') === 'prod') {
            $allowedOrigin = $this->get('service_container')->getParameter('allowed.origin');
            $headers = [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Headers' => $allowedHeaders,
            ];
        }

        return new JsonResponse(
            [],
            200,
            $headers
        );
    }

    /**
     * @param array $statuses
     * @param bool  $includeRepliedToStatuses
     * @return array
     */
    private function extractStatusProperties(array $statuses, bool $includeRepliedToStatuses = false): array
    {
        return array_map(
            function ($status) use ($includeRepliedToStatuses) {
                $defaultStatus = [
                    'status_id' => $status['status_id'],
                    'avatar_url' => 'N/A',
                    'text' => $status['text'],
                    'url' => 'https://twitter.com/' . $status['screen_name'] . '/status/' . $status['status_id'],
                    'retweet_count' => 'N/A',
                    'favorite_count' => 'N/A',
                    'username' => $status['screen_name'],
                    'published_at' => 'N/A',
                ];

                $hasDocumentFromApi = array_key_exists('api_document', $status);

                if (!array_key_exists('original_document', $status) &&
                !$hasDocumentFromApi) {
                    return $defaultStatus;
                }

                if ($hasDocumentFromApi) {
                    $status['original_document'] = $status['api_document'];
                    unset($status['api_document']);
                }

                $decodedDocument = json_decode($status['original_document'], $asAssociativeArray = true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $defaultStatus;
                }

                if (array_key_exists('retweeted_status', $decodedDocument)) {
                    $updatedStatus = $this->updateFromDecodedDocument(
                        $defaultStatus,
                        $decodedDocument['retweeted_status'],
                        $includeRepliedToStatuses
                    );
                    $updatedStatus['username'] = $decodedDocument['retweeted_status']['user']['screen_name'];
                    $updatedStatus['username_of_retweeting_member'] = $defaultStatus['username'];
                    $updatedStatus['retweet'] = true;

                    return $updatedStatus;
                }

                $statusUpdatedFromDecodedDocument = $defaultStatus;
                $updatedStatus = $this->updateFromDecodedDocument(
                    $statusUpdatedFromDecodedDocument,
                    $decodedDocument,
                    $includeRepliedToStatuses
                );
                $updatedStatus['retweet'] = false;

                return $updatedStatus;

            },
            $statuses
        );
    }

    /**
     * @param array $updatedStatus
     * @param array $decodedDocument
     * @param bool  $includeRepliedToStatuses
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function extractConversationProperties(
        array $updatedStatus,
        array $decodedDocument,
        bool $includeRepliedToStatuses = false
    ): array {
        $updatedStatus['in_conversation'] = null;
        if ($includeRepliedToStatuses && array_key_exists('in_reply_to_status_id_str', $decodedDocument) &&
        !is_null($decodedDocument['in_reply_to_status_id_str'])) {
            $updatedStatus['id_of_status_replied_to'] = $decodedDocument['in_reply_to_status_id_str'];
            $updatedStatus['username_of_member_replied_to'] = $decodedDocument['in_reply_to_screen_name'];
            $updatedStatus['in_conversation'] = true;

            $this->statusAccessor = $this->get('weaving_the_web.accessor.status');

            try {
                $repliedToStatus = $this->statusAccessor->refreshStatusByIdentifier(
                    $updatedStatus['id_of_status_replied_to']
                );
            } catch (NotFoundMemberException $notFoundMemberException) {
                $this->statusAccessor->ensureMemberHavingScreenNameExists($notFoundMemberException->screenName);
                $repliedToStatus = $this->statusAccessor->refreshStatusByIdentifier(
                    $updatedStatus['id_of_status_replied_to']
                );
            }

            $repliedToStatus = $this->extractStatusProperties([$repliedToStatus], $includeRepliedToStatuses = true);
            $updatedStatus['status_replied_to'] = $repliedToStatus[0];
        }

        return $updatedStatus;
    }

    /**
     * @param JsonResponse $response
     * @return JsonResponse
     */
    private function setCacheHeaders(JsonResponse $response)
    {
        $response->setCache([
            'public' => true,
            'max_age' => 3600,
            's_maxage' => 3600,
            'last_modified' => new \DateTime(
            // last hour
                (new \DateTime(
                    'now',
                    new \DateTimeZone('UTC'))
                )->modify('-1 hour')->format('Y-m-d H:0'),
                new \DateTimeZone('UTC')
            )
        ]);

        return $response;
    }

    /**
     * @param JsonResponse $response
     * @param              $encodedStatuses
     * @return JsonResponse
     */
    private function setContentLengthHeader(JsonResponse $response, $encodedStatuses)
    {
        $contentLength = strlen($encodedStatuses);
        $response->headers->add([
            'Content-Length' => $contentLength,
            'x-decompressed-content-length' => $contentLength,
            // @see https://stackoverflow.com/a/37931084/282073
            'Access-Control-Expose-Headers' => 'Content-Length, x-decompressed-content-length'
        ]);

        return $response;
    }
}
