<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Domain\Exception\InvalidMemberException;
use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Membership\Infrastructure\Repository\Exception\InvalidMemberIdentifier;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Http\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Infrastructure\PublishersList\Repository\PaginationAwareTrait;
use Assert\Assert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use function is_numeric;
use function sprintf;

/**
 * @method MemberInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberInterface[]    findAll()
 * @method MemberInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    use LoggerTrait;

    private const TABLE_ALIAS = 'm';

    /** @var PublishersListRepository */
    public PublishersListRepository $aggregateRepository;

    use PaginationAwareTrait;

    /**
     * @param SearchParams $searchParams
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param string $maxLikeId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMaxLikeIdForMemberWithScreenName(string $maxLikeId, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($member->maxLikeId === null || ((int)$maxLikeId > (int)$member->maxLikeId)) {
            $member->maxLikeId = $maxLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $maxStatusId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMaxStatusIdForMemberWithScreenName(string $maxStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if ($member->maxStatusId === null || ((int)$maxStatusId > (int)$member->maxStatusId)) {
            $member->maxStatusId = $maxStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param MemberInterface $user
     *
     * @return MemberInterface
     * @throws OptimisticLockException*@throws ORMException
     * @throws ORMException
     */
    public function declareMemberAsFound(MemberInterface $user): MemberInterface
    {
        $user->setNotFound(false);

        return $this->saveMember($user);
    }

    /**
     * @param MemberInterface $user
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMemberAsNotFound(MemberInterface $user): MemberInterface
    {
        $user->setNotFound(true);

        return $this->saveMember($user);
    }

    /**
     * @param string $screenName
     *
     * @return MemberInterface|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMemberAsSuspended(string $screenName): ?MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$member instanceof MemberInterface) {
            return null;
        }

        /** @var MemberInterface $member */
        return $this->suspendMember($screenName);
    }

    /**
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMemberHavingScreenNameNotFound(string $screenName): MemberInterface
    {
        $notFoundMember = $this->make(
            '0',
            $screenName
        );
        $notFoundMember->setNotFound(true);

        return $this->saveMember($notFoundMember);
    }

    /**
     * @param string $minLikeId
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMinLikeIdForMemberWithScreenName(string $minLikeId, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($member->minLikeId === null || ((int)$minLikeId < (int)$member->minLikeId)) {
            $member->minLikeId = $minLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareMinStatusIdForMemberWithScreenName(
        string $minStatusId,
        string $screenName
    ): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if (
            $member->minStatusId === null
            || ((int)$minStatusId < (int)$member->minStatusId)
        ) {
            $member->minStatusId = $minStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareTotalLikesOfMemberWithName(int $totalLikes, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($totalLikes > $member->totalLikes) {
            $member->setTotalLikes($totalLikes);
            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareTotalStatusesOfMemberWithName(int $totalStatuses, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($totalStatuses > $member->totalTweets()) {
            $member->setTotalTweets($totalStatuses);

            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @param $screenName
     *
     * @return MemberInterface|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function declareUserAsNotFoundByUsername($screenName): ?MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$member instanceof MemberInterface) {
            return null;
        }

        return $this->declareMemberAsNotFound($member);
    }

    /**
     * @param string $screenName
     * @param string $twitterId
     * @return Member|MemberInterface
     * @throws InvalidMemberIdentifier
     */
    public function declareUserAsProtected(string $screenName, string $twitterId)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return $this->make(
                $twitterId ?? (string)(int)microtime(true),
                $screenName,
                $protected = true
            );
        }

        $member->setProtected(true);

        return $this->saveMember($member);
    }

    /**
     * @param array $tokenInfo
     *
     * @return MemberInterface|null
     * @throws DBALException
     */
    public function findByAuthenticationToken(array $tokenInfo): ?MemberInterface
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
            SELECT usr_id member_id
            FROM authentication_token a
            LEFT JOIN weaving_user m
            ON a.member_id = m.usr_id
            WHERE a.token = ?
QUERY;
        $statement = $connection->executeQuery(
            $query,
            [$tokenInfo['sub']],
            [\PDO::PARAM_STR]
        );
        $results = $statement->fetchAllAssociative();

        if (
            count($results) !== 1
            || !array_key_exists('member_id', $results[0])
        ) {
            return null;
        }

        $member = $this->findOneBy(['id' => $results[0]['member_id']]);

        if ($member instanceof MemberInterface) {
            return $member;
        }

        return null;
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return array
     * @throws DBALException
     */
    public function findMembers(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $aggregateProperties = $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults($searchParams->getPageSize());

        $results = $queryBuilder->getQuery()->getArrayResult();

        if (count($aggregateProperties) > 0) {
            return array_map(
                function ($result) use ($aggregateProperties) {
                    return array_merge(
                        $result,
                        $aggregateProperties[strtolower($result['name'])]
                    );
                },
                $results
            );
        }

        return $results;
    }

    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getMemberHavingApiKey()
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->andWhere('u.apiKey is not null');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param string $screenName
     *
     * @return int|null
     * @throws \App\Membership\Domain\Exception\InvalidMemberException
     */
    public function getMinPublicationIdForMemberHavingScreenName(string $screenName): ?int
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!($member instanceof MemberInterface)) {
            throw new InvalidMemberException(
                sprintf(
                    'Member with screen name "%s" can not be found',
                    $screenName
                )
            );
        }

        return $member->getMinStatusId();
    }

    public function hasBeenUpdatedBetween7HoursAgoAndNow(string $screenName): bool
    {
        $query = <<< QUERY
            SELECT 
            EXTRACT(
                EPOCH from (
                    NOW()::timestamp -
                    last_status_publication_date::timestamp
                )
            ) > 3600*7 AS has_been_updated_between_seven_hours_ago_and_now
            FROM weaving_user 
            WHERE
            usr_twitter_username = '%s' 
            AND EXTRACT(EPOCH FROM (NOW()::timestamp - last_status_publication_date::timestamp)) < 24 * 3600;
QUERY;

        try {
            $connection = $this->getEntityManager()->getConnection();
            $statement = $connection->executeQuery(
                sprintf(
                    $query,
                    $screenName
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());

            return false;
        }

        $results = $statement->fetchAllAssociative();

        if ($results === []) {
            return false;
        }

        return (bool)$results[0]['has_been_updated_between_seven_hours_ago_and_now'];
    }

    /**
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function incrementTotalLikesOfMemberWithName(
        int    $likesToBeAdded,
        string $screenName
    ): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        $member->setTotalLikes($member->totalLikes() + $likesToBeAdded);

        $this->saveMember($member);

        return $member;
    }

    /**
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function incrementTotalStatusesOfMemberWithName(
        int    $statusesToBeAdded,
        string $screenName
    ): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        $member->setTotalTweets($member->totalTweets() + $statusesToBeAdded);
        $this->saveMember($member);

        return $member;
    }

    /**
     * @param string      $twitterId
     * @param string      $screenName
     * @param bool        $protected
     * @param bool        $suspended
     * @param string|null $description
     * @param int         $totalSubscriptions
     * @param int         $totalSubscribees
     *
     * @return Member
     * @throws InvalidMemberIdentifier
     */
    public function make(
        string $twitterId,
        string $screenName,
        bool   $protected = false,
        bool   $suspended = false,
        string $description = null,
        int    $totalSubscriptions = 0,
        int    $totalSubscribees = 0
    ): MemberInterface
    {
        $member = new Member();

        if (is_numeric($twitterId)) {
            if ((int)$twitterId === 0) {
                throw new InvalidMemberIdentifier(
                    'An identifier should be distinct from 0.'
                );
            }

            $member->setTwitterID($twitterId);
        }

        $member->setTwitterScreenName($screenName);

        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);

        $member->setProtected($protected);
        $member->setSuspended($suspended);
        $member->setNotFound(false);

        if ($description !== null) {
            $member->description = $description;
        }

        $member->totalSubscribees = $totalSubscribees;
        $member->totalSubscriptions = $totalSubscriptions;

        return $member;
    }

    public function memberHavingScreenName(string $screenName): MemberInterface
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $queryBuilder->select('m.id as identifier')
            ->andWhere('LOWER(m.twitter_username) = :screenName');

        $queryBuilder->setParameter('screenName', strtolower($screenName));

        try {
            $memberIdentifier = $queryBuilder->getQuery()->getSingleResult()['identifier'];

            $member = $this->findOneBy(['id' => $memberIdentifier]);

            if (!$member instanceof MemberInterface) {
                $this->logger
                    ->error(
                        'Mismatching member identifier and screen name or removal.',
                        [
                            'identifier' => substr($memberIdentifier, 0, 5),
                            'screen_name' => substr($screenName, 0, 3)
                        ]
                    );

                throw new NoResultException();
            }

        } catch (NoResultException $exception) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                $screenName,
                'member-not-found',
                $exception->getCode(),
                $exception
            );
        }

        return $member;
    }

    public function saveMember(MemberInterface $member): MemberInterface
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($member);
        $entityManager->flush();

        return $member;
    }

    public function saveApiConsumer(
        MemberIdentity $memberIdentity,
        string         $apiKey
    ): MemberInterface
    {
        $member = $this->findOneBy([
            'twitter_username' => $memberIdentity->screenName(),
            'twitterID' => $memberIdentity->id(),
        ]);

        if (!$member instanceof MemberInterface) {
            $member = $this->saveMemberWithAdditionalProps($memberIdentity);
        }

        if ($member->getApiKey() === $apiKey) {
            return $member;
        }

        $member->apiKey = $apiKey;

        return $this->saveMember($member);
    }

    /**
     * @param \App\Twitter\Infrastructure\Http\Resource\MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveMemberFromIdentity(
        MemberIdentity $memberIdentity
    ): MemberInterface
    {
        return $this->saveMemberWithAdditionalProps($memberIdentity);
    }

    /**
     * @param \App\Twitter\Infrastructure\Http\Resource\MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveProtectedMember(
        MemberIdentity $memberIdentity
    ): MemberInterface
    {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            $protected = true
        );
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveSuspendedMember(
        MemberIdentity $memberIdentity
    ): MemberInterface
    {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            $protected = false,
            $suspended = true
        );
    }

    /**
     * @param string $screenName
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMember(string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if ($member instanceof MemberInterface) {
            $member->setSuspended(true);

            return $this->saveUser($member);
        }

        $member = new Member();
        $member->setTwitterScreenName($screenName);
        $member->setTwitterID('0');
        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);
        $member->setEnabled(false);
        $member->setProtected(false);
        $member->setSuspended(true);

        return $this->saveMember($member);
    }

    /**
     * @param int $identifier
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMemberByIdentifier(int $identifier): MemberInterface
    {
        $suspendedMember = $this->findOneBy(['twitterID' => $identifier]);

        if ($suspendedMember instanceof MemberInterface) {
            $suspendedMember->setSuspended(true);

            return $this->saveUser($suspendedMember);
        }

        $suspendedMember = new Member();
        $suspendedMember->setTwitterScreenName((string)$identifier);
        $suspendedMember->setTwitterID((string)$identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setLocked(false);
        $suspendedMember->setEmail('@' . $identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setProtected(false);
        $suspendedMember->setSuspended(true);

        return $this->saveUser($suspendedMember);
    }

    /**
     * @param $identifier
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspendMemberByScreenNameOrIdentifier($identifier)
    {
        if (is_numeric($identifier)) {
            return $this->suspendMemberByIdentifier($identifier);
        }

        return $this->suspendMember($identifier);
    }

    /**
     * @param MemberInterface $member
     *
     * @return MemberInterface
     * @throws OptimisticLockException
     * @throws ORMException
     * @deprecated
     *
     */
    protected function saveUser(MemberInterface $member)
    {
        return $this->saveMember($member);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     *
     * @return array
     * @throws DBALException
     */
    private function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): array
    {
        $queryBuilder->select('m.twitter_username as name');
        $queryBuilder->addSelect('m.url');
        $queryBuilder->addSelect('m.description');
        $queryBuilder->addSelect('m.twitterID as twitterId');
        $queryBuilder->addSelect('m.notFound as isNotFound');
        $queryBuilder->addSelect('m.suspended as isSuspended');
        $queryBuilder->addSelect('m.protected as isProtected');
        $queryBuilder->addSelect('m.id as id');

        if ($searchParams->hasKeyword()) {
            $queryBuilder->andWhere('m.twitter_username like :keyword');
            $queryBuilder->setParameter(
                'keyword',
                sprintf(
                    '%%%s%%',
                    strtr(
                        $searchParams->getKeyword(),
                        [
                            '_' => '\_',
                            '%' => '%%',
                        ]
                    )
                )
            );
        }

        $params = $searchParams->getParams();
        if (array_key_exists('aggregateId', $params)) {
            $aggregates = $this->findRelatedAggregates($searchParams);
            $aggregateProperties = [];
            array_walk(
                $aggregates,
                function ($aggregate) use (&$aggregateProperties) {
                    $aggregate['id'] = (int)$aggregate['id'];
                    $aggregate['totalStatuses'] = (int)$aggregate['totalStatuses'];
                    $aggregate['locked'] = (bool)$aggregate['locked'];

                    if (array_key_exists('unlocked_at', $aggregate)) {
                        $aggregate['unlockedAt'] = $aggregate['unlocked_at'];
                    }

                    if (
                        array_key_exists('unlocked_at', $aggregate)
                        && !is_null($aggregate['unlocked_at'])
                    ) {
                        $aggregate['unlockedAt'] = (new \DateTime(
                            $aggregate['unlocked_at'],
                            new \DateTimeZone('UTC')
                        )
                        )->getTimestamp();
                    }

                    $aggregateProperties[strtolower($aggregate['screenName'])] = $aggregate;
                }
            );

            $screenNames = array_map(
                function ($result) {
                    return $result['screenName'];
                },
                $aggregates
            );
            $queryBuilder->andWhere('m.twitter_username in (:screen_names)');
            $queryBuilder->setParameter('screen_names', $screenNames);

            return $aggregateProperties;
        }

        return [];
    }

    /**
     * @throws NotFoundMemberException
     */
    private function ensureMemberExists(string $screenName): MemberInterface
    {
        Assert::lazy()
            ->that($screenName)
            ->notEmpty()
            ->verifyNow();

        $screenName = strtolower($screenName);

        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$member instanceof MemberInterface) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName($screenName, 'member-not-found');
        }

        return $member;
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return array
     * @throws DBALException
     */
    private function findRelatedAggregates(SearchParams $searchParams): array
    {
        $params = $searchParams->getParams();
        $hasKeyword = $searchParams->hasKeyword();

        $keywordCondition = '';
        if ($hasKeyword) {
            $keywordCondition = 'AND aggregate.screen_name like ?';
        }

        $connection = $this->getEntityManager()->getConnection();
        $query = <<< QUERY
            SELECT 
            aggregate.id,
            aggregate.screen_name AS screenName, 
            aggregate.total_statuses AS totalStatuses,
            aggregate.locked, 
            aggregate.locked_at AS lockedAt,
            aggregate.unlocked_at AS unlockedAt
            FROM publishers_list a
            INNER JOIN publishers_list aggregate
            ON aggregate.screen_name = a.screen_name AND aggregate.screen_name IS NOT NULL
            WHERE a.name in (
                SELECT a.name
                FROM publishers_list a
                WHERE id = ?
            )
            $keywordCondition
            GROUP BY aggregate.id
QUERY;

        $params = [$params['aggregateId']];
        if ($hasKeyword) {
            $keyword = sprintf(
                '%%%s%%',
                strtr(
                    $searchParams->getKeyword(),
                    [
                        '_' => '\_',
                        '%' => '%%',
                    ]
                )
            );
            $params[] = $keyword;
        }

        $paramsTypes = [
            \PDO::PARAM_INT,
        ];
        if ($hasKeyword) {
            $paramsTypes = [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR
            ];
        }

        $statement = $connection->executeQuery(
            $query,
            $params,
            $paramsTypes
        );

        $results = $statement->fetchAllAssociative();

        $results = array_map(
            function (array $aggregate) {
                if ((int)$aggregate['totalStatuses'] <= 0) {
                    $matchingAggregate = $this->aggregateRepository->findOneBy(
                        ['id' => (int)$aggregate['id']]
                    );

                    if ($matchingAggregate instanceof PublishersListInterface) {
                        $this->aggregateRepository->updateTotalStatuses(
                            $aggregate,
                            $matchingAggregate
                        );
                        $aggregate['totalStatuses'] = $matchingAggregate->totalStatus();
                    }
                }

                return $aggregate;
            },
            $results
        );

        try {
            $this->getEntityManager()->flush();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $results;
    }

    /**
     * @return MemberInterface
     * @throws InvalidMemberIdentifier
     */
    private function saveMemberWithAdditionalProps(
        MemberIdentity $memberIdentity,
        bool           $protected = false,
        bool           $suspended = false
    ): MemberInterface
    {
        $member = $this->make(
            $memberIdentity->id(),
            $memberIdentity->screenName(),
            $protected,
            $suspended
        );

        return $this->saveMember($member);
    }
}
