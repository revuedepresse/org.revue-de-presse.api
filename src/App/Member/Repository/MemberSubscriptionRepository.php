<?php

namespace App\Member\Repository;

use App\Http\PaginationParams;
use App\Member\Entity\MemberSubscription;
use App\Member\MemberInterface;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityRepository;
use JsonSchema\Exception\JsonDecodingException;
use WeavingTheWeb\Bundle\ApiBundle\Entity\AggregateIdentity;
use WTW\UserBundle\Repository\UserRepository;

class MemberSubscriptionRepository extends EntityRepository
{
    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscription
     * @return MemberSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMemberSubscription(
        MemberInterface $member,
        MemberInterface $subscription
    ) {
        $memberSubscription = $this->findOneBy(['member' => $member, 'subscription' => $subscription]);

        if (!($memberSubscription instanceof MemberSubscription)) {
            $memberSubscription = new MemberSubscription($member, $subscription);
        }

        $this->getEntityManager()->persist($memberSubscription->markAsNotBeingCancelled());
        $this->getEntityManager()->flush();

        return $memberSubscription;
    }

    /**
     * @param MemberInterface $member
     * @param array           $subscriptions
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findMissingSubscriptions(MemberInterface $member, array $subscriptions)
    {
        $query = <<< QUERY
            SELECT GROUP_CONCAT(sm.usr_twitter_id) subscription_ids
            FROM member_subscription s,
            weaving_user sm
            WHERE sm.usr_id = s.subscription_id
            AND member_id = :member_id1
            AND (s.has_been_cancelled IS NULL OR s.has_been_cancelled = 0)
            AND sm.usr_twitter_id is not null
            AND sm.usr_twitter_id in (:subscription_ids)
QUERY;

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                    ':subscription_ids' => (string) implode(',', $subscriptions)
                ]
            )
        );

        $results = $statement->fetchAll();

        $remainingSubscriptions = $subscriptions;
        if (array_key_exists(0, $results) && array_key_exists('subscription_ids', $results[0])) {
            $subscriptionIds = array_map(
                'intval',
                explode(',', $results[0]['subscription_ids'])
            );
            $remainingSubscriptions = array_diff(
                array_values($subscriptions),
                $subscriptionIds
            );
        }

        return $remainingSubscriptions;
    }

    /**
     * @param MemberInterface $member
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function cancelAllSubscriptionsFor(MemberInterface $member)
    {
        $query = <<< QUERY
            UPDATE member_subscription ms, weaving_user u
            SET has_been_cancelled = 1
            WHERE ms.member_id = :member_id
            AND ms.subscription_id = u.usr_id
            AND u.suspended = 0
            AND u.protected = 0
            AND u.not_found = 0
QUERY;

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [':member_id' => $member->getId()]
            )
        );

        return $statement->closeCursor();
    }

    /**
     * @param MemberInterface        $member
     * @param PaginationParams       $paginationParams
     * @param AggregateIdentity|null $aggregateIdentity
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMemberSubscriptions(
        MemberInterface $member,
        PaginationParams $paginationParams = null,
        AggregateIdentity $aggregateIdentity = null
    ): array {
        $memberSubscriptions = [];

        $totalPages = $this->countMemberSubscriptions($member);
        if ($totalPages) {
            $memberSubscriptions = $this->selectMemberSubscriptions(
                $member,
                $paginationParams,
                $aggregateIdentity
            );
        }

        $aggregates = [];
        if ($paginationParams instanceof PaginationParams) {
            $aggregates = $this->getAggregatesRelatedToMemberSubscriptions(
                $member,
                $paginationParams
            );
        }

        return [
            'subscriptions' => [
                'aggregates' => $aggregates,
                'subscriptions' => $memberSubscriptions
            ],
            'total_subscriptions' => $totalPages,
        ];
    }

    /**
     * @param MemberInterface $member
     * @return array|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countMemberSubscriptions(MemberInterface $member)
    {
        $queryTemplate = <<< QUERY
            SELECT 
            {selection}
            {constraints}
QUERY;
        $query = strtr($queryTemplate, [
            '{selection}' => 'COUNT(*) count_',
            '{constraints}' => $this->getConstraints(),
        ]);

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                ]
            )
        );

        $results = $statement->fetchAll();
        if ($this->emptyResults($results, 'count_')) {
            return 0;
        }

        return $results[0]['count_'];
    }

    /**
     * @param MemberInterface        $member
     * @param PaginationParams|null  $paginationParams
     * @param AggregateIdentity|null $aggregateIdentity
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function selectMemberSubscriptions(
        MemberInterface $member,
        PaginationParams $paginationParams = null,
        AggregateIdentity $aggregateIdentity = null
    ) {
        $query = $this->queryMemberSubscriptions(
            $aggregateIdentity,
            $paginationParams,
            $selection = '',
            $group = '',
            $sort = ''
        );

        $connection = $this->getEntityManager()->getConnection();

        $offset = '';
        $pageSize = '';
        if ($paginationParams instanceof PaginationParams) {
            $offset = $paginationParams->getFirstItemIndex();
            $pageSize = $paginationParams->pageSize;
        }

        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                    ':offset' => $offset,
                    ':page_size' => $pageSize,
                    ':aggregate_id' => (string) $aggregateIdentity
                ]
            )
        );

        $results = $statement->fetchAll();
        if (!array_key_exists(0, $results)) {
            return [];
        }

        return array_map(function (array $row) {
            $row['aggregates'] = json_decode($row['aggregates'], $asArray = true);

            $lastJsonError = json_last_error();
            if ($lastJsonError !== JSON_ERROR_NONE) {
                throw new JsonDecodingException($lastJsonError);
            }

            return $row;
        }, $results);
    }

    public function getSelection()
    {
        return <<<QUERY
            u.usr_twitter_username as username,
            u.usr_twitter_id as member_id,
            u.description,
            u.url,
            IF (
              COALESCE(a.id, 0),
              CONCAT(
                '{',
                GROUP_CONCAT(
                  CONCAT('"', a.id, '": "', a.name, '"') ORDER BY a.name DESC SEPARATOR ","
                ), 
                '}'
              ),
              '{}'
            ) as aggregates
QUERY;
    }

    /**
     * @param AggregateIdentity $aggregateIdentity
     * @return string
     */
    public function getConstraints(AggregateIdentity $aggregateIdentity = null)
    {
        $restrictionByAggregate = '';

        if ($aggregateIdentity) {
            $restrictionByAggregate = sprintf(<<<QUERY
                AND a.name IN ( SELECT name FROM weaving_aggregate WHERE id = %d)
QUERY
            , intval((string) $aggregateIdentity));
        }

        $constraintsTemplates = implode(
            PHP_EOL,
            [
                <<<QUERY
                FROM member_subscription ms,
                weaving_user u
                {join} weaving_aggregate a
                ON a.screen_name = u.usr_twitter_username
                AND a.screen_name IS NOT NULL 
                WHERE member_id = :member_id 
                AND ms.has_been_cancelled = 0
                AND ms.subscription_id = u.usr_id
                AND u.suspended = 0
                AND u.protected = 0
                AND u.not_found = 0
QUERY
                ,
                $restrictionByAggregate
            ]
        );

        return str_replace(
            '{join}',
            $restrictionByAggregate ? 'INNER JOIN' : 'LEFT JOIN',
            $constraintsTemplates
        );
    }

    /**
     * @param AggregateIdentity|null $aggregateIdentity
     * @param PaginationParams|null  $paginationParams
     * @param string                 $selection
     * @param string                 $group
     * @param string                 $sort
     * @return string
     */
    private function queryMemberSubscriptions(
        AggregateIdentity $aggregateIdentity = null,
        PaginationParams $paginationParams  = null,
        string $selection = '',
        string $group = '',
        string $sort = ''
    ): string {
        $queryTemplate = <<< QUERY
            SELECT 
            {selection}
            {constraints}
            {group}
            {limit}
QUERY;

        return strtr($queryTemplate, [
            '{selection}' => $selection ?: $this->getSelection(),
            '{constraints}' => $this->getConstraints($aggregateIdentity),
            '{group}' => $group?: 'GROUP BY u.usr_twitter_username',
            '{sort}' => $sort?: 'ORDER BY u.usr_twitter_username ASC',
            '{limit}' => $paginationParams instanceof PaginationParams ? 'LIMIT :offset, :page_size' : '',
        ]);
    }

    /**
     * @param array $results
     * @param       $column
     * @return bool
     */
    private function emptyResults(array $results, $column): bool
    {
        return !array_key_exists(0, $results) || !array_key_exists($column, $results[0]);
    }

    /**
     * @param MemberInterface  $member
     * @param PaginationParams $paginationParams
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAggregatesRelatedToMemberSubscriptions(
        MemberInterface $member,
        PaginationParams $paginationParams
    ): array {
        $query = sprintf(<<<QUERY
                SELECT CONCAT(
                    '{',
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            '"',
                            select_.id, 
                            '": "', 
                            select_.name, 
                            '"'
                        ) SEPARATOR ', '
                    ),
                    '}'
                ) as aggregates
                FROM (%s) select_
QUERY
            ,
            $this->queryMemberSubscriptions(
                $aggregateIdentity = null,
                $paginationParams,
                'a.name, a.id'
            )
        );

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                    ':offset' => $paginationParams->getFirstItemIndex(),
                    ':page_size' => $paginationParams->pageSize,
                ]
            )
        );
        $aggregateResults = $statement->fetchAll();

        $aggregates = [];
        if (!$this->emptyResults($aggregateResults, 'aggregates')) {
            $aggregates = json_decode(
                $aggregateResults[0]['aggregates'],
                $asArray = true
            );
        }

        return $aggregates;
    }
}
