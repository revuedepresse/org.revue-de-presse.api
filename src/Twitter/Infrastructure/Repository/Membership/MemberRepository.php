<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Membership;

use App\Membership\Domain\Entity\Legacy\Member;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Infrastructure\Repository\Exception\InvalidMemberIdentifier;
use App\Trends\Infrastructure\Repository\PaginationAwareTrait;
use App\Twitter\Domain\Membership\Exception\InvalidMemberException;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Ownership\Domain\Repository\MembersListRepositoryInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Http\SearchParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use function is_numeric;
use function sprintf;

/**
 * @package App\Membership\Infrastructure\Repository
 *
 * @method MemberInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberInterface[]    findAll()
 * @method MemberInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    use LoggerTrait;

    private const TABLE_ALIAS = 'm';

    public MembersListRepositoryInterface $membersListRepository;

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

        if ($member->maxLikeId === null || ((int) $maxLikeId > (int) $member->maxLikeId)) {
            $member->maxLikeId = $maxLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @throws NotFoundMemberException
     */
    public function declareMaxStatusIdForMemberWithScreenName(string $maxStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if ($member->maxStatusId === null || ((int) $maxStatusId > (int) $member->maxStatusId)) {
            $member->maxStatusId = $maxStatusId;
        }

        return $this->saveMember($member);
    }

    public function declareMemberAsFound(MemberInterface $user): MemberInterface
    {
        $user->setNotFound(false);

        return $this->saveMember($user);
    }

    public function declareMemberAsNotFound(MemberInterface $user): MemberInterface
    {
        $user->setNotFound(true);

        return $this->saveMember($user);
    }

    /**
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
     * @throws InvalidMemberIdentifier
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
     * @return MemberInterface
     * @throws NotFoundMemberException
     */
    public function declareMinLikeIdForMemberWithScreenName(string $minLikeId, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($member->minLikeId === null || ((int) $minLikeId < (int) $member->minLikeId)) {
            $member->minLikeId = $minLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @return MemberInterface
     * @throws NotFoundMemberException
     */
    public function declareMinStatusIdForMemberWithScreenName(
        string $minStatusId,
        string $screenName
    ): MemberInterface {
        $member = $this->ensureMemberExists($screenName);

        if (
            $member->minStatusId === null
            || ((int) $minStatusId < (int) $member->minStatusId)
        ) {
            $member->minStatusId = $minStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @throws NotFoundMemberException
     */
    public function declareTotalStatusesOfMemberWithName(int $totalStatuses, string $screenName): MemberInterface
    {
        $member = $this->ensureMemberExists($screenName);

        if ($totalStatuses > $member->totalStatuses) {
            $member->totalStatuses = $totalStatuses;

            $this->saveMember($member);
        }

        return $member;
    }

    public function declareUserAsNotFoundByUsername($screenName): ?MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$member instanceof MemberInterface) {
            return null;
        }

        return $this->declareMemberAsNotFound($member);
    }

    /**
     * @throws \App\Membership\Infrastructure\Repository\Exception\InvalidMemberIdentifier
     */
    public function declareUserAsProtected(string $screenName): MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return $this->make(
                '0',
                $screenName,
                $protected = true
            );
        }

        $member->setProtected(true);

        return $this->saveMember($member);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByAuthenticationToken(array $tokenInfo): ?MemberInterface
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        $query      = <<<QUERY
            SELECT usr_id member_id
            FROM authentication_token a
            LEFT JOIN weaving_user m
            ON a.member_id = m.usr_id
            WHERE a.token = ?
QUERY;
        $statement  = $connection->executeQuery(
            $query,
            [$tokenInfo['sub']],
            [\PDO::PARAM_STR]
        );
        $results    = $statement->fetchAllAssociative();

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
     * @throws InvalidMemberException
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

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function hasBeenUpdatedBetween7HoursAgoAndNow(string $screenName): bool
    {
        $query = <<< QUERY
            SELECT TIME_TO_SEC(
                TIMEDIFF(
                    NOW(),
                    last_status_publication_date
                )
            ) > 3600*7 AS has_been_updated_between_seven_hours_ago_and_now
            FROM weaving_user 
            WHERE
            usr_twitter_username = '%s' 
            AND DATEDIFF(NOW(), last_status_publication_date) < 1;
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

        return (bool) $results[0]['has_been_updated_between_seven_hours_ago_and_now'];
    }

    /**
     * @throws NotFoundMemberException
     */
    public function incrementTotalLikesOfMemberWithName(
        int $likesToBeAdded,
        string $memberName
    ): MemberInterface {
        $member = $this->ensureMemberExists($memberName);

        $member->totalLikes = $member->totalLikes + $likesToBeAdded;

        $this->saveMember($member);

        return $member;
    }

    /**
     * @throws NotFoundMemberException
     */
    public function incrementTotalStatusesOfMemberWithName(
        int $statusesToBeAdded,
        string $memberName
    ): MemberInterface {
        $member = $this->ensureMemberExists($memberName);

        $member->setTotalStatus($member->totalStatus() + $statusesToBeAdded);
        $this->saveMember($member);

        return $member;
    }

    /**
     * @throws \App\Membership\Infrastructure\Repository\Exception\InvalidMemberIdentifier
     */
    public function make(
        string $twitterId,
        string $screenName,
        bool $protected = false,
        bool $suspended = false,
        string $description = null,
        int $totalSubscriptions = 0,
        int $totalSubscribees = 0
    ): MemberInterface {
        $member = new Member();

        if (is_numeric($twitterId)) {
            if ((int) $twitterId === 0) {
                throw new InvalidMemberIdentifier(
                    'An identifier should be distinct from 0.'
                );
            }

            $member->setTwitterID($twitterId);
        }

        $member->setScreenName($screenName);

        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);

        $member->setProtected($protected);
        $member->setSuspended($suspended);
        $member->setNotFound(false);

        if ($description !== null) {
            $member->description = $description;
        }

        $member->totalSubscribees   = $totalSubscribees;
        $member->totalSubscriptions = $totalSubscriptions;

        return $member;
    }

    public function saveMember(MemberInterface $member): MemberInterface
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($member);
        $entityManager->flush();

        return $member;
    }

    /**
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveApiConsumer(
        MemberIdentity $memberIdentity,
        string $apiKey
    ): MemberInterface {
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
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveMemberFromIdentity(
        MemberIdentity $memberIdentity
    ): MemberInterface {
        return $this->saveMemberWithAdditionalProps($memberIdentity);
    }

    /**
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveProtectedMember(
        MemberIdentity $memberIdentity
    ): MemberInterface {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            $protected = true
        );
    }

    /**
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveSuspendedMember(
        MemberIdentity $memberIdentity
    ): MemberInterface {
        return $this->saveMemberWithAdditionalProps(
            $memberIdentity,
            $protected = false,
            $suspended = true
        );
    }

    /**
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
        $member->setTwitterUsername($screenName);
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
        $suspendedMember->setTwitterUsername((string) $identifier);
        $suspendedMember->setTwitterID((string) $identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setLocked(false);
        $suspendedMember->setEmail('@' . $identifier);
        $suspendedMember->setEnabled(false);
        $suspendedMember->setProtected(false);
        $suspendedMember->setSuspended(true);

        return $this->saveUser($suspendedMember);
    }

    /**
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
     * @deprecated use MemberRepository->saveMember instead
     */
    protected function saveUser(MemberInterface $member)
    {
        return $this->saveMember($member);
    }

    /**
     * @throws \Exception
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
            $aggregates          = $this->findRelatedAggregates($searchParams);
            $aggregateProperties = [];
            array_walk(
                $aggregates,
                function ($list) use (&$aggregateProperties) {
                    $list['id']            = (int) $list['id'];
                    $list['totalStatuses'] = (int) $list['totalStatuses'];
                    $list['locked']        = (bool) $list['locked'];

                    if (array_key_exists('unlocked_at', $list)) {
                        $list['unlockedAt'] = $list['unlocked_at'];
                    }

                    if (
                        array_key_exists('unlocked_at', $list)
                        && !is_null($list['unlocked_at'])
                    ) {
                        $list['unlockedAt'] = (new \DateTime(
                            $list['unlocked_at'],
                            new \DateTimeZone('UTC')
                        )
                        )->getTimestamp();
                    }

                    $aggregateProperties[strtolower($list['screenName'])] = $list;
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
    private function ensureMemberExists(string $memberName): MemberInterface
    {
        $member = $this->findOneBy(['twitter_username' => $memberName]);
        if (!$member instanceof MemberInterface) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName($memberName);
        }

        return $member;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function findRelatedAggregates(SearchParams $searchParams): array
    {
        $params     = $searchParams->getParams();
        $hasKeyword = $searchParams->hasKeyword();

        $keywordCondition = '';
        if ($hasKeyword) {
            $keywordCondition = 'AND aggregate.screen_name like ?';
        }

        $connection = $this->getEntityManager()->getConnection();
        $query      = <<< QUERY
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
            $keyword  = sprintf(
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
            function (array $list) {
                if ((int) $list['totalStatuses'] <= 0) {
                    $matchingAggregate = $this->membersListRepository->findOneBy(
                        ['id' => (int) $list['id']]
                    );

                    $this->membersListRepository->updateTotalStatuses(
                        $list,
                        $matchingAggregate
                    );
                    $list['totalStatuses'] = $matchingAggregate->totalStatuses;
                }

                return $list;
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
     * @throws InvalidMemberIdentifier
     */
    private function saveMemberWithAdditionalProps(
        MemberIdentity $memberIdentity,
        bool $protected = false,
        bool $suspended = false
    ): MemberInterface {
        $member = $this->make(
            $memberIdentity->id(),
            $memberIdentity->screenName(),
            $protected,
            $suspended
        );

        return $this->saveMember($member);
    }
}
