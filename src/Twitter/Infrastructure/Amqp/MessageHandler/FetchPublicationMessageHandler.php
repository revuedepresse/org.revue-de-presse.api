<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageHandler;

use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Twitter\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\Twitter\Collector\PublicationCollectorInterface;
use App\Twitter\Domain\Curation\LikedStatusCollectionAwareInterface;
use App\Twitter\Domain\Api\TwitterErrorAwareInterface;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Exception;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use function sprintf;

/**
 * @package App\Twitter\Infrastructure\Amqp\MessageHandler
 */
class FetchPublicationMessageHandler implements MessageSubscriberInterface
{
    use LoggerTrait;
    use MemberRepositoryTrait;

    /**
     * @return iterable
     */
    public static function getHandledMessages(): iterable
    {
        yield FetchMemberStatus::class => [
            'from_transport' => 'news_status'
        ];

        yield FetchMemberLikes::class => [
            'from_transport' => 'news_likes'
        ];
    }

    /**
     * @var TokenRepositoryInterface
     */
    public TokenRepositoryInterface $tokenRepository;

    /**
     * @var PublicationCollectorInterface $collector
     */
    protected PublicationCollectorInterface $collector;

    /**
     * @param FetchPublicationInterface $message
     *
     * @return bool
     */
    public function __invoke(FetchPublicationInterface $message): bool
    {
        $success = false;

        try {
            $options = $this->processMessage($message);
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $options = [
            LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES => $message->shouldFetchLikes(),
            $message::publishers_list_ID                              => $message->aggregateId(),
            $message::BEFORE                                           => $message->dateBeforeWhichStatusAreCollected(),
            'count'                                                    => 200,
            'oauth'                                                    => $options[TokenInterface::FIELD_TOKEN],
            $message::SCREEN_NAME                                      => $message->screenName(),
        ];

        try {
            $success = $this->collector->collect(
                $options,
                $greedy = true
            );
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
                $this->memberRepository->declareUserAsNotFoundByUsername($options['screen_name']);
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
        } catch (\Throwable $exception) {
            $this->logger->critical(
                $exception->getMessage(),
                ['stacktrace' => $exception->getTraceAsString()]
            );
        }

        return $success;
    }

    /**
     * @param FetchPublicationInterface $message
     *
     * @return array
     * @throws Exception
     */
    public function processMessage(FetchPublicationInterface $message): array
    {
        $oauthToken = $this->extractOAuthToken($message);

        $this->collector->setupAccessor($oauthToken);

        return $oauthToken;
    }

    /**
     * @param PublicationCollectorInterface $collector
     */
    public function setCollector(PublicationCollectorInterface $collector)
    {
        $this->collector = $collector;
    }

    private function extractOAuthToken(FetchPublicationInterface $message): array
    {
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
            TokenInterface::FIELD_TOKEN  => $token->getOAuthToken(),
            TokenInterface::FIELD_SECRET => $token->getOAuthSecret(),
        ];
    }
}