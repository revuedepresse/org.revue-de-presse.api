<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use App\Aggregate\Entity\TimelyStatus;
use App\Aggregate\Repository\TimelyStatusRepository;
use App\Member\MemberInterface;
use App\Status\Entity\LikedStatus;
use App\Status\Repository\LikedStatusRepository;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

/**
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AggregateRepository extends ResourceRepository
{
    /**
     * @var TimelyStatusRepository
     */
    public $timelyStatusRepository;

    /**
     * @var LikedStatusRepository
     */
    public $likedStatusRepository;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @param string $screenName
     * @param string $listName
     * @return Aggregate
     */
    public function make(string $screenName, string $listName)
    {
        $aggregate = $this->findByRemovingDuplicates(
            $screenName,
            $listName
        );

        if ($aggregate instanceof Aggregate) {
            return $aggregate;
        }

        return new Aggregate($screenName, $listName);
    }

    /**
     * @param MemberInterface $member
     * @param \stdClass       $list
     * @return null|object|Aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addMemberToList(
        MemberInterface $member,
        \stdClass $list
    ) {
        $aggregate = $this->findOneBy([
            'name' => $list->name,
            'screenName' => $member->getTwitterUsername()
        ]);

        if (!($aggregate instanceof Aggregate)) {
            $aggregate = $this->make($member->getTwitterUsername(), $list->name);
        }

        $aggregate->listId = $list->id_str;

        return $this->save($aggregate);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function selectAggregatesForWhichNoStatusHasBeenCollected(): array
    {
        $selectAggregates = <<<QUERY
            SELECT 
            a.id aggregate_id, 
            screen_name member_screen_name, 
            `name` aggregate_name,
            u.usr_twitter_id member_id
            FROM weaving_aggregate a, weaving_user u
            WHERE screen_name IS NOT NULL 
            AND a.screen_name = u.usr_twitter_username
            AND id NOT IN (
                SELECT aggregate_id FROM weaving_status_aggregate
            );
QUERY;

        $statement = $this->getEntityManager()->getConnection()->executeQuery($selectAggregates);

        return $statement->fetchAll();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function lockAggregate(Aggregate $aggregate)
    {
        $aggregate->lock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unlockAggregate(Aggregate $aggregate)
    {
        $aggregate->unlock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Aggregate $aggregate
     * @return Aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Aggregate $aggregate)
    {
        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();

        return $aggregate;
    }

    /**
     * @param string $screenName
     * @param string $listName
     * @return null|object
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function findByRemovingDuplicates(
        string $screenName,
        string $listName
    ) {
        $aggregate = $this->findOneBy([
            'screenName' => $screenName,
            'name' => $listName
        ]);

        if ($aggregate instanceof Aggregate) {
            $aggregates = $this->findBy([
                'screenName' => $screenName,
                'name' => $listName
            ]);

            if (count($aggregates) > 1) {
                foreach ($aggregates as $index => $aggregate) {
                    if ($index === 0) {
                        continue;
                    }

                    $statuses = $this->statusRepository
                        ->findByAggregate($aggregate);

                    foreach ($statuses as $status) {
                        /** @var StatusInterface $status */
                        $status->removeFrom($aggregate);
                        $status->addToAggregates($aggregates[0]);
                    }

                    $timelyStatuses = $this->timelyStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var TimelyStatus $timelyStatus */
                    foreach ($timelyStatuses as $timelyStatus) {
                        $timelyStatus->updateAggregate($aggregates[0]);
                    }

                    $likedStatuses = $this->likedStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var LikedStatus $likedStatus */
                    foreach ($likedStatuses as $likedStatus) {
                        $likedStatus->setAggregate($aggregates[0]);
                    }
                }

                $this->getEntityManager()->flush();

                foreach ($aggregates as $index => $aggregate) {
                    if ($index === 0) {
                        continue;
                    }

                    $this->getEntityManager()->remove($aggregate);
                }

                $this->getEntityManager()->flush();
            }
        }

        return $aggregate;
    }
}
