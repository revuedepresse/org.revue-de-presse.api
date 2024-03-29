<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Membership\Domain\Exception\MembershipException;
use App\Twitter\Domain\Amqp\ResourceProcessor\PublishersListProcessorInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;
use App\Twitter\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Twitter\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\TwitterListCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberIdentityProcessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Exception\EmptyListException;
use App\Twitter\Infrastructure\Http\Exception\CanNotReplaceAccessTokenException;
use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_unshift;
use function sprintf;

class PublishersListProcessor implements PublishersListProcessorInterface
{
    use MemberIdentityProcessorTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use TwitterListCollectedEventRepositoryTrait;
    use TokenChangeTrait;
    use TranslatorTrait;

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        TranslatorInterface $translator,
        LoggerInterface     $logger
    ) {
        $this->httpClient = $httpClient;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function processPublishersList(
        PublishersList $list,
        TokenInterface $token,
        CurationRulesetInterface $ruleset
    ): int {
        if ($ruleset->isCurationByListActive($list)) {
            $eventRepository = $this->publishersListCollectedEventRepository;
            $memberCollection = $eventRepository->collectedListOwnedByMember(
                $this->httpClient,
                [
                    $eventRepository::OPTION_PUBLISHERS_LIST_ID => $list->id(),
                    $eventRepository::OPTION_PUBLISHERS_LIST_NAME => $list->name()
                ]
            );
            $memberCollection = $this->addOwnerToListOptionally(
                $memberCollection,
                $ruleset
            );

            if ($memberCollection->isEmpty()) {
                EmptyListException::throws(
                    sprintf(
                        'List "%s" has no members',
                        $list->name()
                    )
                );
            }

            if ($memberCollection->isNotEmpty()) {
                $this->logger->info(
                    sprintf(
                        'About to publish messages for members in list "%s"',
                        $list->name()
                    )
                );
            }

            $publishedMessages = $this->processMemberOriginatingFromListWithToken(
                $memberCollection,
                $list,
                $token,
                $ruleset
            );

            try {
                // Change token for each list unless there is only one single token available
                // Members lists can only be accessed by authenticated users owning the lists
                // See also https://dev.twitter.com/rest/reference/get/lists/ownerships
                $this->tokenChange->replaceAccessToken(
                    $token,
                    $this->httpClient
                );
            } catch (CanNotReplaceAccessTokenException $exception) {
                // keep going with the current token
            }

            return $publishedMessages;
        }

        return 0;
    }

    private function processMemberOriginatingFromListWithToken(
        MemberCollectionInterface $members,
        PublishersList $list,
        TokenInterface $token,
        CurationRulesetInterface $ruleset
    ): int {
        $publishedMessages = 0;

        /** @var MemberIdentity $memberIdentity */
        foreach ($members->toArray() as $memberIdentity) {
            try {
                $publishedMessages += $this->memberIdentityProcessor->process(
                    $memberIdentity,
                    $ruleset,
                    $token,
                    $list
                );
            } catch (ContinuePublicationException $exception) {
                $this->logger->info($exception->getMessage());

                continue;
            } catch (StopPublicationException $exception) {
                if ($exception->getPrevious() instanceof MembershipException) {
                    continue;
                }

                $this->logger->error($exception->getMessage());

                break;
            }  catch (Exception $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    [
                        'screen_name' => $memberIdentity->screenName(),
                        'stacktrace' => $exception->getTraceAsString()
                    ]
                );

                continue;
            }
        }

        return $publishedMessages;
    }

    private function addOwnerToListOptionally(
        MemberCollectionInterface $memberCollection,
        CurationRulesetInterface $ruleset
    ): MemberCollectionInterface
    {
        $members = $memberCollection->toArray();

        if ($ruleset->isListOwnerTweetsCurationActive()) {
            $eventRepository = $this->memberProfileCollectedEventRepository;
            $additionalMember = $eventRepository->collectedMemberProfile(
                $this->httpClient,
                [$eventRepository::OPTION_SCREEN_NAME => $ruleset->whoseListSubscriptionsAreCurated()]
            );
            array_unshift($members, $additionalMember);
            $ruleset->isListOwnerIncluded(false);
        }

        return MemberCollection::fromArray($members);
    }
}
