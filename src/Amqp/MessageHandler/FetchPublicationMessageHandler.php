<?php
declare(strict_types=1);

namespace App\Amqp\MessageHandler;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Infrastructure\Amqp\Message\FetchMemberStatuses;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Operation\OperationClock;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Twitter\Api\TwitterErrorAwareInterface;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use App\Twitter\Serializer\UserStatus;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ReflectionException;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use function array_key_exists;
use function sprintf;

/**
 * @package App\Amqp\MessageHandler
 */
class FetchPublicationMessageHandler implements MessageSubscriberInterface
{
    use LoggerTrait;

    /**
     * @var OperationClock
     */
    public OperationClock $operationClock;

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
     * @var TokenRepositoryInterface
     */
    public TokenRepositoryInterface $tokenRepository;

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
            'from_transport' => 'news_status'
        ];
    }

    /**
     * @param FetchMemberStatuses $message
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ApiRateLimitingException
     * @throws ReadOnlyApplicationException
     * @throws UnexpectedApiResponseException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws ReflectionException
     * @throws ORMException
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
            $message::AGGREGATE_ID                                     => $message->aggregateId(),
            $message::BEFORE                                           => $message->before(),
            'count'                                                    => 200,
            'oauth'                                                    => $options[TokenInterface::FIELD_TOKEN],
            $message::SCREEN_NAME                                      => $message->screenName(),
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
                || \in_array(
                    $unavailableResource->getCode(),
                    [
                        TwitterErrorAwareInterface::ERROR_NOT_FOUND,
                        TwitterErrorAwareInterface::ERROR_SUSPENDED_USER
                    ],
                    true
                )
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
     * @param FetchMemberStatuses $message
     *
     * @return array
     * @throws Exception
     */
    public function processMessage(FetchMemberStatuses $message): array {
        $oauthToken = $this->extractOAuthToken($message);

        $this->serializer->setupAccessor($oauthToken);

        return $oauthToken;
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
     * @param FetchMemberStatuses $message
     *
     * @return array
     */
    private function extractOAuthToken(FetchMemberStatuses $message): array {
        $token = $message->token();

        if ($token->isValid()) {
            return [
                TokenInterface::FIELD_TOKEN  => $token->getOAuthToken(),
                TokenInterface::FIELD_SECRET => $token->getOAuthSecret(),
            ];
        }

        /** @var Token $token */
        $token = $this->tokenRepository->findFirstUnfrozenToken();

        return [
            TokenInterface::FIELD_TOKEN => $token->getOAuthToken(),
            TokenInterface::FIELD_SECRET => $token->getOAuthSecret(),
        ];
    }
}