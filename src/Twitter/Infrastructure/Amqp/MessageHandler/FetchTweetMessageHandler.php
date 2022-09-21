<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageHandler;

use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Curation\Curator\TweetCuratorInterface;
use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\TwitterAPIAwareInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchSearchQueryMatchingTweetInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Entity\Token;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use function sprintf;

class FetchTweetMessageHandler implements MessageSubscriberInterface
{
    use LoggerTrait;
    use MemberRepositoryTrait;

    public static function getHandledMessages(): iterable
    {
        yield FetchTweetInterface::class => [
            'from_transport' => 'publications'
        ];
    }

    public TokenRepositoryInterface $tokenRepository;

    protected TweetCuratorInterface $curator;

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(FetchTweetInterface $message): bool
    {
        $success = false;

        try {
            $options = $this->processMessage($message);
            $options[$message::BEFORE] = $message->dateBeforeWhichStatusAreCollected();
            $options['oauth'] = $options[TokenInterface::FIELD_TOKEN];
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        if ($message instanceof FetchAuthoredTweetInterface) {
            $options['count'] = 200;
            $options[$message::SCREEN_NAME] = $message->screenName();
            $options[$message::TWITTER_LIST_ID] = $message->listId();
        } elseif ($message instanceof FetchSearchQueryMatchingTweetInterface) {
            $options['count'] = 100;
            $options[$message::SEARCH_QUERY] = $message->searchQuery();
        }

        try {
            $success = $this->curator->curateTweets(
                $options,
                greedy: true
            );

            if (!$success) {
                $this->logger->info(
                    sprintf(
                        'Re-queuing message for %s in list %d',
                        $options[FetchAuthoredTweetInterface::SCREEN_NAME],
                        $options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]
                    )
                );
            }
        } catch (UnavailableResourceException $unavailableResource) {
            $userNotFound = $unavailableResource->getCode() === TwitterAPIAwareInterface::ERROR_USER_NOT_FOUND;
            if ($userNotFound) {
                $this->memberRepository->declareUserAsNotFoundByUsername($options['screen_name']);
            }

            if (
                $unavailableResource instanceof ProtectedAccountException
                || $userNotFound
                || \in_array(
                    $unavailableResource->getCode(),
                    [
                        TwitterAPIAwareInterface::ERROR_NOT_FOUND,
                        TwitterAPIAwareInterface::ERROR_SUSPENDED_USER
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
     * @throws Exception
     */
    public function processMessage(FetchTweetInterface $message): array
    {
        $oauthToken = $this->extractOAuthToken($message);

        $this->curator->setupAccessor($oauthToken);

        return $oauthToken;
    }

    public function setCurator(TweetCuratorInterface $curator)
    {
        $this->curator = $curator;
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
