<?php

namespace App\StatusCollection\Messaging;

use App\Member\MemberInterface;
use App\Security\AccessTokenInterface;
use App\StatusCollection\Messaging\Exception\InvalidMemberAggregate;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;

class MessagePublisher
{
    /**
     * @var Producer
     */
    public $aggregateLikesProducer;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @var Producer
     */
    public $aggregateStatusesProducer;

    /**
     * @param MemberInterface      $member
     * @param AccessTokenInterface $accessToken
     */
    public function publishMemberAggregateMessage(MemberInterface $member, AccessTokenInterface $accessToken)
    {
        $body = [
            'token' => $accessToken->getAccessToken(),
            'secret' => $accessToken->getAccessTokenSecret(),
            'consumer_token' => $accessToken->getConsumerKey(),
            'consumer_secret' => $accessToken->getConsumerSecret()
        ];

        $username = $member->getTwitterUsername();
        $body['screen_name'] = $username;

        $aggregate = $this->aggregateRepository->getMemberAggregateByUsername($username);

        if (!($aggregate instanceof Aggregate)) {
            InvalidMemberAggregate::guardAgainstInvalidUsername($username);
        }

        $body['aggregate_id'] = $aggregate->getId();

        $this->aggregateLikesProducer->setContentType('application/json');
        $this->aggregateLikesProducer->publish(serialize(json_encode($body)));

        $this->aggregateStatusesProducer->setContentType('application/json');
        $this->aggregateStatusesProducer->publish(serialize(json_encode($body)));
    }
}
