<?php

namespace Amqp\Twitter;

use App\Member\MemberInterface;
use App\Operation\OperationClock;
use App\Status\LikedStatusCollectionAwareInterface;
use Doctrine\ORM\EntityRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AmqpMessage;
use Psr\Log\LoggerInterface;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Api\TwitterErrorAwareInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStatus implements ConsumerInterface
{
    const ERROR_CODE_USER_NOT_FOUND = 100;

    /**
     * @var OperationClock
     */
    public $operationClock;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus $serializer
     */
    protected $serializer;

    /**
     * @param $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @var \WTW\UserBundle\Repository\MemberRepository
     */
    protected $userRepository;

    /**
     * @var TokenRepository
     */
    public $tokenRepository;

    /**
     * @param EntityRepository $userRepository
     */
    public function setUserRepository(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param AmqpMessage $message
     * @return bool|mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function execute(AmqpMessage $message)
    {
        try {
            $options = $this->parseMessage($message);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }
 
        $options = [
            LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES => $this->extractIntentToCollectLikes($options),
            'aggregate_id' => $this->extractAggregateId($options),
            'before' => $this->extractBeforeOption($options),
            'count' => 200,
            'oauth' => $options['token'],
            'screen_name' => $options['screen_name'],
        ];

        try {
            $success = $this->serializer->serialize($options, $greedy = true);
            if (!$success) {
                $this->logger->info(sprintf(
                    'Re-queuing message for %s in aggregate %d',
                    $options['screen_name'],
                    $options['aggregate_id']
                ));
            }
        } catch (UnavailableResourceException $unavailableResource) {
            $userNotFound = $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_USER_NOT_FOUND;
            if ($userNotFound) {
                $this->userRepository->declareUserAsNotFoundByUsername($options['screen_name']);
            }

            if (
                $unavailableResource instanceof ProtectedAccountException ||
                $userNotFound ||
                $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_NOT_FOUND ||
                $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_SUSPENDED_USER
            ) {
                /**
                 * This message should not be processed again for protected accounts,
                 * nor for suspended accounts
                 */
                $success = true;
                $this->logger->info($unavailableResource->getMessage());
            } else {
                $success = false;
                $this->logger->error($unavailableResource->getMessage());
            }
        }

        return $success;
    }

    /**
     * @param $screenName
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleNotFoundUsers($screenName)
    {
        $member = $this->userRepository->findOneBy(
            ['twitter_username' => $screenName]
        );

        if (!($member instanceof MemberInterface)) {
            $message = sprintf(
                'User with screen name "%s" could not be found via Twitter API not in database',
                $screenName
            );
            throw new \Exception($message, self::ERROR_CODE_USER_NOT_FOUND);
        }

        return $this->userRepository->declareUserAsNotFound($member);
    }

    /**
     * @param AmqpMessage $message
     * @return array
     * @throws \Exception
     */
    public function parseMessage(AmqpMessage $message): array
    {
        $options = json_decode(unserialize($message->body), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Valid credentials are required');
        }

        return $this->setupCredentials($options);
    }

    /**
     * @param array $tokens
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function setupCredentials(array $tokens): array
    {
        if ((!array_key_exists('token', $tokens) ||
            !array_key_exists('secret', $tokens)) &&
            !array_key_exists('bearer', $tokens)) {
            /** @var Token $token */
            $token = $this->tokenRepository->findFirstUnfrozenToken();
            $tokens['token'] = $token->getOauthToken();
            $tokens['secret'] = $token->getOauthTokenSecret();
        }

        $this->serializer->setupAccessor($tokens);

        return $tokens;
    }

    /**
     * @param $options
     * @return null
     */
    protected function extractAggregateId($options)
    {
        if (array_key_exists('aggregate_id', $options)) {
            $aggregateId = $options['aggregate_id'];
        } else {
            $aggregateId = null;
        }

        return $aggregateId;
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function extractIntentToCollectLikes(array $options): bool
    {
        if (!array_key_exists(LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES, $options)) {
            return false;
        }

        return $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES];
    }

    /**
     * @param $options
     * @return null
     */
    protected function extractBeforeOption($options)
    {
        if (array_key_exists('before', $options)) {
            $before = $options['before'];
        } else {
            $before = null;
        }

        return $before;
    }
}
