<?php
declare(strict_types=1);

namespace App\Amqp\MessageHandler;

use App\Amqp\Message\FetchMemberStatuses;
use App\Api\Entity\Token;
use App\Api\Repository\TokenRepository;
use App\Membership\Entity\MemberInterface;
use App\Membership\Repository\MemberRepository;
use App\Operation\OperationClock;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Twitter\Api\TwitterErrorAwareInterface;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use App\Twitter\Serializer\UserStatus;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use function array_key_exists;
use function sprintf;

/**
 * @package App\Amqp\MessageHandler
 */
class FetchMemberStatusMessageHandler implements MessageSubscriberInterface
{
    private const ERROR_CODE_USER_NOT_FOUND = 100;

    /**
     * @var OperationClock
     */
    public OperationClock $operationClock;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @var UserStatus $serializer
     */
    protected UserStatus $serializer;

    /**
     * @param UserStatus $serializer
     */
    public function setSerializer(UserStatus $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @var MemberRepository
     */
    protected MemberRepository $userRepository;

    /**
     * @var TokenRepository
     */
    public TokenRepository $tokenRepository;

    /**
     * @param MemberRepository $userRepository
     */
    public function setUserRepository(MemberRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return iterable
     */
    public static function getHandledMessages()
    : iterable
    {
        yield FetchMemberStatuses::class => [
            'from_transport' => 'async'
        ];
    }

    /**
     * @param FetchMemberStatuses $message
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function __invoke(FetchMemberStatuses $message)
    {
        try {
            $options = $this->processMessage($message);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $options = [
            LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES => $this->extractIntentToCollectLikes($options),
            'aggregate_id'                                             => $this->extractAggregateId($options),
            'before'                                                   => $this->extractBeforeOption($options),
            'count'                                                    => 200,
            'oauth'                                                    => $options['token'],
            'screen_name'                                              => $options['screen_name'],
        ];

        try {
            $success = $this->serializer->serialize($options, $greedy = true);
            if (!$success) {
                $this->logger->info(
                    sprintf(
                        'Re-queuing message for %s in aggregate %d',
                        $options['screen_name'],
                        $options['aggregate_id']
                    )
                );
            }
        } catch (UnavailableResourceException $unavailableResource) {
            $userNotFound = $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_USER_NOT_FOUND;
            if ($userNotFound) {
                $this->userRepository->declareUserAsNotFoundByUsername($options['screen_name']);
            }

            if (
                $unavailableResource instanceof ProtectedAccountException
                || $userNotFound
                || $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_NOT_FOUND
                || $unavailableResource->getCode() === TwitterErrorAwareInterface::ERROR_SUSPENDED_USER
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
     *
     * @return MemberInterface
     * @throws OptimisticLockException
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
            throw new Exception($message, self::ERROR_CODE_USER_NOT_FOUND);
        }

        return $this->userRepository->declareUserAsNotFound($member);
    }

    /**
     * @param FetchMemberStatuses $message
     *
     * @return array
     * @throws Exception
     */
    public function processMessage(
        FetchMemberStatuses $message
    ): array {
        $tokens = [];

        if (
            array_key_exists('token', $message->credentials())
            && array_key_exists(
                'secret',
                $message->credentials()
            )
        ) {
            $this->serializer->setupAccessor([
                'token' => $message->credentials()['token'],
                'secret' => $message->credentials()['secret'],
            ]);

            return $tokens;
        }

        /** @var Token $token */
        $token = $this->tokenRepository->findFirstUnfrozenToken();

        $tokens['token'] = $token->getOauthToken();
        $tokens['secret'] = $token->getOauthTokenSecret();

        $this->serializer->setupAccessor($tokens);

        return $tokens;
    }

    /**
     * @param $options
     *
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
     *
     * @return bool
     */
    protected function extractIntentToCollectLikes(array $options)
    : bool {
        if (!array_key_exists(LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES, $options)) {
            return false;
        }

        return $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES];
    }

    /**
     * @param $options
     *
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