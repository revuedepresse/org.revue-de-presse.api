<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Controller;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Cache\RedisCache;
use App\Conversation\ConversationAwareTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationRepositoryTrait;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\DBAL\Exception\ConnectionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use const JSON_THROW_ON_ERROR;

class PublicationController
{
    use PublicationRepositoryTrait;
    use CorsHeadersAwareTrait;
    use ConversationAwareTrait;
    use LoggerTrait;

    private RedisCache $redisCache;

    public function getPublication(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        try {
            $statusId = $request->attributes->get('publicationId');

            $status     = $this->findStatusOrFetchItByIdentifier(
                $statusId,
                $shouldRefreshStatus = $request->query->has('refresh')
            );
            $statusCode = 200;

            $statuses = [$status];
            if ($status === null) {
                $statuses = [];
            }

            $statuses = $this->extractStatusProperties($statuses, $includeRepliedToStatuses = true);

            $response = new JsonResponse(
                $statuses,
                $statusCode,
                $this->getAccessControlOriginHeaders(
                    $this->environment,
                    $this->allowedOrigin
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
            $this->logger->critical('Could not connect to the database');
        } catch (NotFoundStatusException $exception) {
            $errorMessage = sprintf("Could not find status with id '%s'", $statusId);
            $this->logger->info($errorMessage);

            return $this->setCacheHeaders(
                new JsonResponse(
                    ['error' => $errorMessage],
                    404,
                    $this->getAccessControlOriginHeaders(
                        $this->environment,
                        $this->allowedOrigin
                    )
                )
            );
        } catch (Exception $exception) {
            return $this->getExceptionResponse($exception);
        }
    }

    public function getPublications(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $encodedPublications = $this->getCachedPublications($request);

        if (!$encodedPublications) {
            $publications = $this->publicationRepository
                ->getLatestPublications()
                ->toArray();

            $encodedPublications = json_encode(
                $publications,
                JSON_THROW_ON_ERROR
            );

            $client   = $this->redisCache->getClient();
            $cacheKey = $this->getCacheKey($request);
            $client->setex($cacheKey, 3600, $encodedPublications);
        }

        $publications = json_decode(
            $encodedPublications,
            $asArray = true,
            512,
            JSON_THROW_ON_ERROR
        );

        return new JsonResponse(
            $publications,
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            ),
        );
    }

    public function setCache(RedisCache $cache): self
    {
        $this->redisCache = $cache;

        return $this;
    }

    /**
     * @param Exception $exception
     * @param null      $message
     *
     * @return JsonResponse
     */
    protected function getExceptionResponse(
        Exception $exception,
        $message = null
    ) {
        if (is_null($message)) {
            $data = ['error' => $exception->getMessage()];
        } else {
            $data = ['error' => $message];
        }

        $this->logger->critical($data['error']);

        $statusCode = 500;

        return new JsonResponse(
            $data,
            $statusCode,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }

    private function getCacheKey(Request $request): string
    {
        return $request->get('_controller');
    }

    private function getCachedPublications(Request $request): string
    {
        $publications = '';
        if ($this->willCacheResponse($request)) {
            $client       = $this->redisCache->getClient();
            $cacheKey     = $this->getCacheKey($request);
            $publications = $client->get($cacheKey);

            if ($publications === null) {
                return '';
            }
        }

        return $publications;
    }

    /**
     * @param JsonResponse $response
     *
     * @return JsonResponse
     * @throws Exception
     */
    private function setCacheHeaders(JsonResponse $response)
    {
        $response->setCache(
            [
                'public'        => true,
                'max_age'       => 3600,
                's_maxage'      => 3600,
                'last_modified' => new \DateTime(
                // last hour
                    (new \DateTime(
                        'now',
                        new \DateTimeZone('UTC')
                    )
                    )->modify('-1 hour')->format('Y-m-d H:0'),
                    new \DateTimeZone('UTC')
                )
            ]
        );

        return $response;
    }

    /**
     * @param JsonResponse $response
     * @param              $encodedStatuses
     *
     * @return JsonResponse
     */
    private function setContentLengthHeader(
        JsonResponse $response,
        $encodedStatuses
    ): Response {
        $contentLength = strlen($encodedStatuses);
        $response->headers->add(
            [
                'Content-Length'                => $contentLength,
                'x-decompressed-content-length' => $contentLength,
                // @see https://stackoverflow.com/a/37931084/282073
                'Access-Control-Expose-Headers' => 'Content-Length, x-decompressed-content-length'
            ]
        );

        return $response;
    }

    private function willCacheResponse(Request $request): bool
    {
        $willCacheResponse = true;
        if (
            $request->headers->has('x-no-cache')
            && $request->headers->get('x-no-cache')
        ) {
            $willCacheResponse = !(bool) $request->headers->get('x-no-cache');
        }

        return $willCacheResponse;
    }
}