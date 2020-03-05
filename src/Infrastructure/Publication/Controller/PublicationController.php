<?php
declare(strict_types=1);

namespace App\Infrastructure\Publication\Controller;

use App\Accessor\Exception\NotFoundStatusException;
use App\Conversation\ConversationAwareTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\DBAL\Exception\ConnectionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicationController
{
    use ConversationAwareTrait;
    use CorsHeadersAwareTrait;
    use LoggerTrait;

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

            $status = $this->findStatusOrFetchItByIdentifier(
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

            return $this->setCacheHeaders(new JsonResponse(
                  ['error' => $errorMessage],
                  404,
                  $this->getAccessControlOriginHeaders(
                      $this->environment,
                      $this->allowedOrigin
                  )
              ));
        } catch (Exception $exception) {
            return $this->getExceptionResponse($exception);
        }
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

    /**
     * @param JsonResponse $response
     *
     * @return JsonResponse
     * @throws Exception
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
    private function setContentLengthHeader(
        JsonResponse $response,
        $encodedStatuses
    ): Response {
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