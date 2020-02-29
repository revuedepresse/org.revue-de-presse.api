<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;
use App\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Infrastructure\DependencyInjection\Membership\MemberIdentityProcessorTrait;
use App\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Exception\EmptyListException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_unshift;
use function sprintf;

class PublicationListProcessor implements PublicationListProcessorInterface
{
    use MemberIdentityProcessorTrait;
    use TokenChangeTrait;
    use TranslatorTrait;

    /**
     * @var ApiAccessorInterface
     */
    private ApiAccessorInterface $accessor;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        ApiAccessorInterface $accessor,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->accessor = $accessor;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @param PublicationList              $list
     * @param TokenInterface               $token
     * @param PublicationStrategyInterface $strategy
     *
     * @return int
     * @throws InvalidSerializedTokenException
     */
    public function processPublicationList(
        PublicationList $list,
        TokenInterface $token,
        PublicationStrategyInterface $strategy
    ): int {
        if ($strategy->shouldProcessList($list)) {
            $memberCollection = $this->accessor->getListMembers($list->id());
            $memberCollection = $this->addOwnerToListOptionally(
                $memberCollection,
                $strategy
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
                $strategy
            );

            // Change token for each list
            // Members lists can only be accessed by authenticated users owning the lists
            // See also https://dev.twitter.com/rest/reference/get/lists/ownerships
            $this->tokenChange->replaceAccessToken(
                $token,
                $this->accessor
            );

            return $publishedMessages;
        }

        return 0;
    }

    /**
     * @param MemberCollection $members
     * @param PublicationList  $list
     * @param TokenInterface   $token
     *
     * @return int
     * @throws Exception
     */
    private function processMemberOriginatingFromListWithToken(
        MemberCollection $members,
        PublicationList $list,
        TokenInterface $token,
        PublicationStrategyInterface $strategy
    ): int {
        $publishedMessages = 0;

        /** @var MemberIdentity $memberIdentity */
        foreach ($members->toArray() as $memberIdentity) {
            try {
                $this->memberIdentityProcessor->process(
                    $memberIdentity,
                    $strategy,
                    $token,
                    $list
                );
                $publishedMessages++;
            } catch (ContinuePublicationException $exception) {
                $this->logger->info($exception->getMessage());

                continue;
            } catch (StopPublicationException $exception) {
                $this->logger->error($exception->getMessage());

                break;
            }
        }

        return $publishedMessages;
    }

    /**
     * @param MemberCollection             $memberCollection
     * @param PublicationStrategyInterface $strategy
     *
     * @return MemberCollection
     */
    private function addOwnerToListOptionally(
        MemberCollection $memberCollection,
        PublicationStrategyInterface $strategy
    ): MemberCollection
    {
        $members = $memberCollection->toArray();
        if ($strategy->shouldIncludeOwner()) {
            $additionalMember = $this->accessor->getMemberProfile(
                $strategy->onBehalfOfWhom()
            );
            array_unshift($members, $additionalMember);
            $strategy->willIncludeOwner(false);
        }

        return MemberCollection::fromArray($members);
    }
}