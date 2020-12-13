<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Api\Accessor;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;

class RefreshStatusMapping implements MappingAwareInterface
{
    use ApiAccessorTrait;
    use LoggerTrait;
    use PublicationPersistenceTrait;
    use StatusRepositoryTrait;

    private array $oauthTokens;

    public function __construct(Accessor $accessor)
    {
        $this->apiAccessor = $accessor;
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
        $this->apiAccessor->propagateNotFoundStatuses = true;

        $this->apiAccessor->setUserToken($oauthTokens['token']);
        $this->apiAccessor->setUserSecret($oauthTokens['secret']);

        if (array_key_exists('consumer_token', $oauthTokens)) {
            $this->apiAccessor->setConsumerKey($oauthTokens['consumer_token']);
            $this->apiAccessor->setConsumerSecret($oauthTokens['consumer_secret']);
        }
    }

    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status {
        try {
            $apiDocument = $this->apiAccessor->showStatus($status->getStatusId());
        } catch (NotFoundStatusException $exception) {
            return $status;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $status;
        }

        // TODO point at Status Logger
        $reachBeforeRefresh = $this->statusRepository->extractReachOfStatus($status);

        $aggregate = $status->getAggregates()->first();
        if (!($aggregate instanceof Aggregate)) {
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
            $this->apiAccessor->ensureMemberHavingNameExists($exception->screenName);
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
