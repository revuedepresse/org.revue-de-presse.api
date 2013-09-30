<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\NoResultException;

/**
 * Class UserStreamRepository
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStreamRepository extends ResourceRepository
{
    public function getAlias()
    {
        return 'ust';
    }

    /**
     * @param $statuses
     * @param $identifier
     */
    public function saveStatuses($statuses, $identifier)
    {
        $entityManager = $this->getEntityManager();
        $extracts = $this->extractProperties($statuses, function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

        foreach ($extracts as $key => $extract) {
            if (!$this->existsAlready($extract['identifier'], $extract['status_id'])) {
                /**
                 * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream
                 */
                $userStream = $this->queryFactory->makeUserStream($extract);
                $userStream->setIdentifier($extract['identifier']);
                $entityManager->persist($userStream);
            } else {
                unset($extracts[$key]);
            }
        }

        $entityManager->flush();

        return $extracts;
    }

    /**
     * @param $statuses
     * @param $setter
     * @return array
     */
    protected function extractProperties($statuses, callable $setter)
    {
        $extracts = [];

        foreach ($statuses as $status) {
            if (property_exists($status, 'text')) {
                $extract = [
                    'text' => $status->text,
                    'screen_name' => $status->user->screen_name,
                    'name' => $status->user->name,
                    'user_avatar' => $status->user->profile_image_url,
                    'status_id' => $status->id_str,
                ];
                $extract = $setter($extract);
                $extracts[] = $extract;
            }
        }

        return $extracts;
    }

    public function existsAlready($oauthToken, $statusId)
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->select('count(u.id) as count_')
            ->andWhere('u.identifier = :oauthToken')
            ->andWhere('u.statusId = :statusId');
        $queryBuilder->setParameter('oauthToken', $oauthToken);
        $queryBuilder->setParameter('statusId', $statusId);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param $oauthToken
     * @return mixed
     */
    public function countStatuses($oauthToken)
    {
        $countQueryBuilder = $this->createQueryBuilder('u');
        $countQueryBuilder->select('count(u.id) as count_')
            ->where('u.identifier = :oauth');
        $countQueryBuilder->setParameter('oauth', $oauthToken);
        $count = $countQueryBuilder->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @return mixed
     */
    public function findNextMaxStatus($oauthToken, $screenName)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.identifier = :identifier')
            ->orderBy('s.statusId + 1', 'asc')
            ->setMaxResults(1);

        $queryBuilder->setParameter('identifier', $oauthToken);
        $queryBuilder->setParameter('screenName', $screenName);

        try {
            $singleResult = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return [];
        }

        return $singleResult;
    }
}
