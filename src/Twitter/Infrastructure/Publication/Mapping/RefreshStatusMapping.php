<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Infrastructure\Http\Client\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;

class RefreshStatusMapping implements MappingAwareInterface
{
    use HttpClientTrait;
    use LoggerTrait;
    use PublicationPersistenceTrait;
    use StatusRepositoryTrait;

    private array $oauthTokens;

    public function __construct(HttpClientInterface $accessor)
    {
        $this->apiClient = $accessor;
    }

    /**
     * @param $oauthTokens
     */
    public function setOAuthTokens($oauthTokens) {
        $this->oauthTokens = $oauthTokens;
        $this->setupAccessor($oauthTokens);
    }

    /**
     * @param $oauthTokens
     */
    protected function setupAccessor($oauthTokens)
    {
        $this->apiClient->propagateNotFoundStatuses = true;

        $this->apiClient->setAccessToken($oauthTokens['token']);
        $this->apiClient->setAccessTokenSecret($oauthTokens['secret']);

        if (array_key_exists('consumer_token', $oauthTokens)) {
            $this->apiClient->setConsumerKey($oauthTokens['consumer_token']);
            $this->apiClient->setConsumerSecret($oauthTokens['consumer_secret']);
        }
    }

    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status {
        try {
            $apiDocument = $this->apiClient->showStatus($status->getStatusId());
        } catch (NotFoundStatusException $exception) {
            return $status;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $status;
        }

        // TODO point at Status Logger
        $reachBeforeRefresh = $this->statusRepository->extractReachOfStatus($status);

        $aggregate = $status->getAggregates()->first();
        if (!($aggregate instanceof PublishersList)) {
            $aggregate = null;
        }

        try {
            $this->publicationPersistence->persistStatusPublication(
                [$apiDocument],
                $status->getIdentifier(),
                $aggregate,
                $this->logger
            );
        } catch (NotFoundMemberException $exception) {
            $this->apiClient->ensureMemberHavingNameExists($exception->screenName);
            $this->publicationPersistence->persistStatusPublication(
                [$apiDocument],
                $status->getIdentifier(),
                $aggregate,
                $this->logger
            );
        }

        $refreshedStatus = $this->statusRepository->findOneBy(['id' => $status->getId()]);
        $reachAfterRefresh = $this->statusRepository->extractReachOfStatus($refreshedStatus);

        $this->logger->info(sprintf(
            'Status with id %s had retweet count going from %d to %d',
            $status->getStatusId(),
            $reachBeforeRefresh['retweet_count'],
            $reachAfterRefresh['retweet_count']
        ));
        $this->logger->info(sprintf(
            'Status with id %s had favorite count going from %d to %d',
            $status->getStatusId(),
            $reachBeforeRefresh['favorite_count'],
            $reachAfterRefresh['favorite_count']
        ));

        return $refreshedStatus;
    }
}
