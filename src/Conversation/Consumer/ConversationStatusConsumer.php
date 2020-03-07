<?php

namespace App\Conversation\Consumer;

use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\StatusAccessor;
use App\Aggregate\AggregateAwareTrait;
use App\Amqp\AmqpMessageAwareTrait;
use App\Api\Entity\Status;
use App\Api\Repository\PublicationListRepository;
use App\Api\Repository\StatusRepository;
use App\Conversation\ConversationAwareTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Operation\OperationClock;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;

class ConversationStatusConsumer
{
    use AggregateAwareTrait;
    use AmqpMessageAwareTrait;
    use ConversationAwareTrait;
    use LoggerTrait;

    private const ERROR_CODE_USER_NOT_FOUND = 100;

    public OperationClock $operationClock;

    public EntityManagerInterface $entityManager;

    public PublicationListRepository $aggregateRepository;

    protected MemberRepository $userRepository;

    /**
     * @param EntityRepository $userRepository
     */
    public function setUserRepository(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param AmqpMessage $message
     *
     * @return bool|mixed
     * @throws MappingException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    public function execute(AmqpMessage $message)
    {
        if ($this->operationClock->shouldSkipOperation()) {
            return true;
        }

        try {
            $options = $this->parseMessage($message);
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
            $this->statusRepository->shouldExtractProperties = false;
            $status = $this->statusAccessor->refreshStatusByIdentifier(
                $options['status_id'],
                $skipExistingStatus = false,
                $extractProperties = false
            );

            $member = $this->ensureStatusAuthorExists($status);

            $aggregate = $this->getListAggregateByName($member->getTwitterUsername(), $options['aggregate_name']);
        } catch (NotFoundMemberException $notFoundMemberException) {
            [$aggregate, $status] = $this->handleMemberNotFoundException($notFoundMemberException, $options);
        } catch (NotFoundStatusException $exception) {
            $this->handleStatusNotFoundException($options);
        } catch (UnavailableResourceException $exception) {
            $this->handleProtectedStatusException($options);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        } finally {
            if (!isset($status) && (
                $exception instanceof NotFoundStatusException ||
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

            $this->statusRepository->shouldExtractProperties = true;
            $statusProperties = $this->findStatusOrFetchItByIdentifier($options['status_id']);

            try {
                $this->extractStatusProperties([$statusProperties], $includeRepliedToStatuses = true);
            } catch (NotFoundStatusException $notFoundMemberException) {
                return $this->handleStatusNotFoundException($options);
            } catch (UnavailableResourceException $exception) {
                $this->handleProtectedStatusException($options);
            }
        }


        return $status instanceof Status;
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
     * @param NotFoundMemberException $notFoundMemberException
     * @param array                   $options
     *
     * @return array
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws MappingException
     */
    private function handleMemberNotFoundException(
        NotFoundMemberException $notFoundMemberException,
        array $options
    ): array {
        $member = $this->statusAccessor->ensureMemberHavingNameExists($notFoundMemberException->screenName);
        $aggregate = $this->getListAggregateByName($member->getTwitterUsername(), $options['aggregate_name']);
        $status = $this->statusAccessor->refreshStatusByIdentifier(
            $options['status_id'],
            $skipExistingStatus = false,
            $extractProperties = false
        );

        return array($aggregate, $status);
    }

    /**
     * @param $options
     * @return bool
     */
    private function handleStatusNotFoundException($options): bool
    {
        $errorMessage = sprintf("Could not find status with id '%s'", $options['status_id']);
        $this->logger->info($errorMessage);

        return true;
    }

    /**
     * @param $options
     * @return bool
     */
    private function handleProtectedStatusException($options): bool
    {
        $errorMessage = sprintf("Could not collect protected status with id '%s'", $options['status_id']);
        $this->logger->info($errorMessage);

        return true;
    }

    /**
     * @param Status $status
     *
     * @return Member
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    private function ensureStatusAuthorExists(Status $status): Member
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $status->getScreenName()]);
        if (!$member instanceof MemberInterface) {
            $member = $this->statusAccessor->ensureMemberHavingNameExists($status->getScreenName());
            $existingMember = $this->userRepository->findOneBy(['twitterID' => $member->getTwitterID()]);

            if ($existingMember) {
                return $existingMember;
            }

            $this->userRepository->saveMember($member);
        }

        return $member;
    }
}
