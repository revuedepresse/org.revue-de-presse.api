<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageHandler;

use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweet;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Domain\Collector\PublicationCollectorInterface;
use App\Twitter\Domain\Api\TwitterErrorAwareInterface;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Exception;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use function sprintf;

class FetchPublicationMessageHandler implements MessageSubscriberInterface
{
    use LoggerTrait;
    use MemberRepositoryTrait;

    /**
     * @return iterable
     */
    public static function getHandledMessages(): iterable
    {
        yield FetchTweet::class => [
            'from_transport' => 'publications'
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
     * @param FetchTweetInterface $message
     *
     * @return bool
     */
    public function __invoke(FetchTweetInterface $message): bool
    {
        $success = false;

        try {
            $options = $this->processMessage($message);
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $options = [
            $message::PUBLISHERS_LIST_ID => $message->aggregateId(),
            $message::BEFORE             => $message->dateBeforeWhichStatusAreCollected(),
            'count'                      => 200,
            'oauth'                      => $options[TokenInterface::FIELD_TOKEN],
            $message::SCREEN_NAME        => $message->screenName(),
        ];

        try {
            $success = $this->collector->collect(
                $options,
                greedy: true
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
     * @param FetchTweetInterface $message
     *
     * @return array
     * @throws Exception
     */
    public function processMessage(FetchTweetInterface $message): array
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

    private function extractOAuthToken(FetchTweetInterface $message): array
    {
        $token = $message->token();

        if ($token->isValid()) {
            return [
                TokenInterface::FIELD_TOKEN  => $token->getAccessToken(),
                TokenInterface::FIELD_SECRET => $token->getAccessTokenSecret(),
            ];
        }

        /** @var Token $token */
        $token = $this->tokenRepository->findFirstUnfrozenToken();

        return [
            TokenInterface::FIELD_TOKEN  => $token->getAccessToken(),
            TokenInterface::FIELD_SECRET => $token->getAccessTokenSecret(),
        ];
    }
}