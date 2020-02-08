<?php

namespace App\Twitter\Controller;

use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\StatusAccessor;
use App\Conversation\ConversationAwareTrait;
use App\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

/**
 * @package App\Twitter\Controller
 */
class TweetController extends Controller
{
    use ConversationAwareTrait;
    use CorsHeadersAwareTrait;

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
            return $this->getCorsOptionsResponse(
                $this->get('service_container')->getParameter('kernel.environment'),
                $this->get('service_container')->getParameter('allowed.origin')
            );
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

            $this->statusAccessor = $this->get('weaving_the_web.accessor.status');
            $statuses = $this->extractStatusProperties($statuses, $includeRepliedToStatuses = false);

            $response = new JsonResponse(
                $statuses,
                $statusCode,
                $this->getAccessControlOriginHeaders(
                    $this->get('service_container')->getParameter('kernel.environment'),
                    $this->get('service_container')->getParameter('allowed.origin')
                )
            );

            $encodedStatuses = json_encode($statuses);
            $this->setContentLengthHeader($response, $encodedStatuses);

            return $this->setCacheHeaders($response);
        } catch (DriverException|DBALException $driverException) {
            $this->get('logger')->critical($driverException->getMessage());
            return new JsonResponse(['error' => 'Too many connections'], 503);
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
     *     "/likes",
     *     name="weaving_the_web_liked_statuses"
     * )
     *
     * @Extra\Method({"GET", "OPTIONS"})
     *
     * @Extra\Cache(public=true)
     */
    public function getLikedStatusesAction(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->get('service_container')->getParameter('kernel.environment'),
                $this->get('service_container')->getParameter('allowed.origin')
            );
        }

        try {
            $this->statusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
            // Look for statuses collected by any given access token
            // (there is no restriction at this point of the implementation)
            $this->statusRepository->setOauthTokens([]);

            $likedStatuses = $this->statusRepository->findLikedStatuses();
            $statusCode = 200;

            $likedStatuses = $this->extractStatusProperties($likedStatuses, $includeRepliedToStatuses = false);

            $response = new JsonResponse(
                $likedStatuses,
                $statusCode,
                $this->getAccessControlOriginHeaders(
                    $this->get('service_container')->getParameter('kernel.environment'),
                    $this->get('service_container')->getParameter('allowed.origin')
                )
            );

            $encodedStatuses = json_encode($likedStatuses);
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
            return $this->getCorsOptionsResponse(
                $this->get('service_container')->getParameter('kernel.environment'),
                $this->get('service_container')->getParameter('allowed.origin')
            );
        }

        try {
            $this->statusRepository = $this->get('weaving_the_web_twitter.repository.read.status');
            $statusId = $request->attributes->get('id');

            $this->statusAccessor = $this->get('weaving_the_web.accessor.status');
            $status = $this->findStatusOrFetchItByIdentifier(
                $statusId,
                $shouldRefreshStatus = $request->query->has('refresh')
            );
            $statusCode = 200;

            $statuses = [$status];
            if (is_null($status)) {
                $statuses = [];
            }

            $statuses = $this->extractStatusProperties($statuses, $includeRepliedToStatuses = true);

            $response = new JsonResponse(
                $statuses,
                $statusCode,
                $this->getAccessControlOriginHeaders(
                    $this->get('service_container')->getParameter('kernel.environment'),
                    $this->get('service_container')->getParameter('allowed.origin')
                )
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
                $this->getAccessControlOriginHeaders(
                    $this->get('service_container')->getParameter('kernel.environment'),
                    $this->get('service_container')->getParameter('allowed.origin')
                )
            ));
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

        return new JsonResponse(
            $data,
            $statusCode,
            $this->getAccessControlOriginHeaders(
                $this->get('service_container')->getParameter('kernel.environment'),
                $this->get('service_container')->getParameter('allowed.origin')
            )
        );
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
