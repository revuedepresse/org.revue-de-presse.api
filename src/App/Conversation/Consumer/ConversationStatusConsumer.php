<?php

namespace App\Conversation\Consumer;

use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\StatusAccessor;
use App\Aggregate\AggregateAwareTrait;
use App\Amqp\AmqpMessageAwareTrait;
use App\Conversation\ConversationAwareTrait;
use App\Operation\OperationClock;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

use PhpAmqpLib\Message\AmqpMessage;

use Psr\Log\LoggerInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;
use WTW\UserBundle\Entity\User;

class ConversationStatusConsumer implements ConsumerInterface
{
    use AggregateAwareTrait;
    use ConversationAwareTrait;
    use AmqpMessageAwareTrait;

    const ERROR_CODE_USER_NOT_FOUND = 100;

    /**
     * @var OperationClock
     */
    public $operationClock;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

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
     * @var StatusAccessor
     */
    public $statusAccessor;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var \WTW\UserBundle\Repository\UserRepository
     */
    protected $userRepository;

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
        if ($this->operationClock->shouldSkipOperation()) {
            return true;
        }

        try {
            $options = $this->parseMessage($message);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $statusId = intval(trim($options['status_id']));
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
            list($aggregate, $status) = $this->handleMemberNotFoundException($notFoundMemberException, $options);
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
     * @param $notFoundMemberException
     * @param $options
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function handleMemberNotFoundException($notFoundMemberException, $options): array
    {
        $member = $this->statusAccessor->ensureMemberHavingScreenNameExists($notFoundMemberException->screenName);
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
     * @return User
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function ensureStatusAuthorExists(Status $status): User
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $status->getScreenName()]);
        if (!$member instanceof User) {
            $member = $this->statusAccessor->ensureMemberHavingScreenNameExists($status->getScreenName());
            $existingMember = $this->userRepository->findOneBy(['twitterID' => $member->getTwitterID()]);

            if ($existingMember) {
                return $existingMember;
            }

            $this->userRepository->saveMember($member);
        }

        return $member;
    }
}
