<?php

namespace App\StatusCollection\Mapping;

use App\Accessor\Exception\NotFoundStatusException;
use Psr\Log\LoggerInterface;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use App\Api\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;

class RefreshStatusMapping implements MappingAwareInterface
{
    /**
     * @var Accessor
     */
    private $accessor;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var array
     */
    private $oauthTokens;

    public function __construct(Accessor $accessor)
    {
        $this->accessor = $accessor;
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
        $this->accessor->propagateNotFoundStatuses = true;

        $this->accessor->setUserToken($oauthTokens['token']);
        $this->accessor->setUserSecret($oauthTokens['secret']);

        if (array_key_exists('consumer_token', $oauthTokens)) {
            $this->accessor->setConsumerKey($oauthTokens['consumer_token']);
            $this->accessor->setConsumerSecret($oauthTokens['consumer_secret']);
        }
    }

    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status {
        try {
            $apiDocument = $this->accessor->showStatus($status->getStatusId());
        } catch (NotFoundStatusException $exception) {
            return $status;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $status;
        }

        $reachBeforeRefresh = $this->statusRepository->extractReachOfStatus($status);

        $aggregate = $status->getAggregates()->first();
        if (!($aggregate instanceof Aggregate)) {
            $aggregate = null;
        }

        try {
            $this->statusRepository->saveStatuses(
                [$apiDocument],
                $status->getIdentifier(),
                $aggregate,
                $this->logger
            );
        } catch (NotFoundMemberException $exception) {
            $this->accessor->ensureMemberHavingNameExists($exception->screenName);
            $this->statusRepository->saveStatuses(
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
