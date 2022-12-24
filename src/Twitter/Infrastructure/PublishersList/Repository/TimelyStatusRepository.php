<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\PublishersList\Repository;

use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Infrastructure\PublishersList\Entity\TimelyStatus;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Repository\PublishersListRepository;
use App\Conversation\ConversationAwareTrait;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Domain\Publication\Repository\TimelyStatusRepositoryInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

class TimelyStatusRepository extends ServiceEntityRepository implements TimelyStatusRepositoryInterface
{
    private const TABLE_ALIAS = 't';

    use ConversationAwareTrait;

    use PaginationAwareTrait;

    public function selectStatuses()
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $queryBuilder->select(
            [
                's.userAvatar as author_avatar',
                's.text',
                's.screenName as screen_name',
                's.id',
                's.statusId as status_id',
                's.starred',
                's.apiDocument original_document'
            ]
        )
            ->leftJoin(self::TABLE_ALIAS.'.tweet', 's')
            ->orderBy(self::TABLE_ALIAS.'.timeRange', 'asc')
            ->orderBy(self::TABLE_ALIAS.'.publicationDateTime', 'desc')
            ->setMaxResults(50)
        ;

        return $queryBuilder;
    }

    public function fromTweetInList(
        TweetInterface $tweet,
        PublishersList $list = null
    ): TimeRangeAwareInterface {
        $timelyStatus = $this->findOneBy([
            'tweet' => $tweet
        ]);

        if ($timelyStatus instanceof TimelyStatus) {
            return $timelyStatus->updateTimeRange();
        }

        return new TimelyStatus(
            $tweet,
            $list,
            $tweet->getCreatedAt()
        );
    }

    public function saveTimelyStatus(
        TimelyStatus $timelyStatus
    ): TimelyStatus {
        $this->getEntityManager()->persist($timelyStatus);
        $this->getEntityManager()->flush();

        return $timelyStatus;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @throws \Exception
     */
    public function findStatuses(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults($searchParams->getPageSize());

        $queryBuilder->groupBy($this->getUniqueIdentifier());
        $queryBuilder->orderBy(self::TABLE_ALIAS.'.publicationDateTime', 'DESC');

        $results = $queryBuilder->getQuery()->getArrayResult();
        $tweets = array_map(
            function ($tweet) {
                $extractedProperties = [
                    'status' => $this->extractStatusProperties(
                        [$tweet],
                        false)[0]
                ];

                unset($tweet['original_document']);
                unset($tweet['screen_name']);
                unset($tweet['author_avatar']);
                unset($tweet['status_id']);

                return array_merge($tweet, $extractedProperties);
            },
            $results
        );

        return $tweets;
    }

    /**
     * @throws \Exception
     */
    private function applyCriteria(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ) {
        $queryBuilder->select('s.apiDocument as original_document');
        $queryBuilder->addSelect(self::TABLE_ALIAS.'.memberName as screen_name');
        $queryBuilder->addSelect(self::TABLE_ALIAS.'.memberName as screenName');
        $queryBuilder->addSelect(self::TABLE_ALIAS.'.twitterListName as twitterListName');
        $queryBuilder->addSelect('a.id as aggregateId');
        $queryBuilder->addSelect('s.id as id');
        $queryBuilder->addSelect('s.statusId as twitterId');
        $queryBuilder->addSelect('s.userAvatar as author_avatar');
        $queryBuilder->addSelect('s.text');
        $queryBuilder->addSelect('s.statusId as status_id');

        if ($searchParams->hasKeyword()) {
            $queryBuilder->andWhere('s.text like :keyword');
            $queryBuilder->setParameter(
                'keyword',
                sprintf('%%%s%%', $searchParams->getKeyword())
            );
        }

        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.tweet', 's');
        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.publisherList', 'a');

        $params = $searchParams->getParams();
        if (array_key_exists('memberName', $params)) {
            $queryBuilder->andWhere(self::TABLE_ALIAS.'.memberName = :member_name');
            $queryBuilder->setParameter('member_name', $params['memberName']);
        }
    }

    public function getUniqueIdentifier(): string
    {
        return 's.id';
    }
}
