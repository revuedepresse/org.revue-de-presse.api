<?php

namespace App\Conversation\Consumer;

use App\Conversation\ConversationAwareTrait;
use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\Http\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\PublishersList\TwitterListAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;

class ConversationStatusConsumer
{
    use TwitterListAwareTrait;
    use ConversationAwareTrait;
    use LoggerTrait;

    private const ERROR_CODE_USER_NOT_FOUND = 100;

    public EntityManagerInterface $entityManager;

    public PublishersListRepository $aggregateRepository;

    protected MemberRepositoryInterface $memberRepository;

    public function setMemberRepository(MemberRepositoryInterface $memberRepository): self
    {
        $this->memberRepository = $memberRepository;

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    public function execute()
    {
        try {
            // FIXME
            $options = [
                'status_id' => '1',
                'screen_name' => 'johndoe',
            ];
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $statusId = (int) trim($options['status_id']);
        if (!$statusId) {
            return true;
        }

        $options = [
            'aggregate_name' => $this->extractAggregateName($options),
            'screen_name' => $options['screen_name'],
            'status_id' => $statusId,
        ];

        try {
            $this->tweetRepository->shouldExtractProperties = false;
            $status = $this->tweetAwareHttpClient->refreshStatusByIdentifier(
                $options['status_id'],
                $skipExistingStatus = false,
                $extractProperties = false
            );

            $member = $this->ensureStatusAuthorExists($status);

            $aggregate = $this->aggregateRepository->byName($member->twitterScreenName(), $options['aggregate_name']);
        } catch (NotFoundMemberException $notFoundMemberException) {
            [$aggregate, $status] = $this->handleMemberNotFoundException($notFoundMemberException, $options);
        } catch (TweetNotFoundException $exception) {
            $this->handleStatusNotFoundException($options);
        } catch (UnavailableResourceException $exception) {
            $this->handleProtectedStatusException($options);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        } finally {
            if (!isset($status) && (
                $exception instanceof TweetNotFoundException ||
                $exception instanceof UnavailableResourceException
            )) {
                return true;
            }

            $aggregates = $status->getAggregates();
            if (!$aggregates->contains($aggregate)) {
                $status->addToAggregates($aggregate);
            }

            $this->entityManager->persist($status);
            $this->entityManager->flush();

            $this->tweetRepository->shouldExtractProperties = true;
            $statusProperties = $this->findStatusOrFetchItByIdentifier($options['status_id']);

            try {
                $this->extractStatusProperties([$statusProperties], $includeRepliedToStatuses = true);
            } catch (TweetNotFoundException $notFoundMemberException) {
                return $this->handleStatusNotFoundException($options);
            } catch (UnavailableResourceException $exception) {
                $this->handleProtectedStatusException($options);
            }
        }


        return $status instanceof Tweet;
    }

    /**
     * @param $options
     * @return null
     */
    protected function extractAggregateName($options)
    {
        if (array_key_exists('aggregate_name', $options)) {
            $aggregateName = $options['aggregate_name'];
        } else {
            $aggregateName = null;
        }

        return $aggregateName;
    }

    /**
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function handleMemberNotFoundException(
        NotFoundMemberException $notFoundMemberException,
        array $options
    ): array {
        $member = $this->tweetAwareHttpClient->ensureMemberHavingNameExists($notFoundMemberException->screenName);
        $aggregate = $this->byName($member->twitterScreenName(), $options['aggregate_name']);
        $status = $this->tweetAwareHttpClient->refreshStatusByIdentifier(
            $options['status_id'],
            $skipExistingStatus = false,
            $extractProperties = false
        );

        return array($aggregate, $status);
    }

    private function handleStatusNotFoundException($options): bool
    {
        $errorMessage = sprintf("Could not find status with id '%s'", $options['status_id']);
        $this->logger->info($errorMessage);

        return true;
    }

    private function handleProtectedStatusException($options): bool
    {
        $errorMessage = sprintf("Could not collect protected status with id '%s'", $options['status_id']);
        $this->logger->info($errorMessage);

        return true;
    }

    private function ensureStatusAuthorExists(Tweet $status): Member
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $status->getScreenName()]);
        if (!($member instanceof MemberInterface)) {
            $member = $this->tweetAwareHttpClient->ensureMemberHavingNameExists($status->getScreenName());
            $existingMember = $this->memberRepository->findOneBy(['twitterID' => $member->twitterId()]);

            if ($existingMember) {
                return $existingMember;
            }

            $this->memberRepository->saveMember($member);
        }

        return $member;
    }
}
