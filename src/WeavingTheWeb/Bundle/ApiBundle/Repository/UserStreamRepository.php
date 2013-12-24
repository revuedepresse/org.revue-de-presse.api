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
     * @return array
     */
    public function saveStatuses($statuses, $identifier)
    {
        $entityManager = $this->getEntityManager();
        $extracts = $this->extractProperties($statuses, function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

        foreach ($extracts as $key => $extract) {
            if (!$this->existsAlready($extract['identifier'], $extract['screen_name'], $extract['status_id'])) {

                if ($this->existsAlready($extract['identifier'], $extract['screen_name'], $extract['status_id'], false)) {
                    /**
                     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
                     */
                    $userStreamRepository = $entityManager->getRepository('WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream');
                    /**
                     * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream
                     */
                    $userStream = $userStreamRepository->findOneBy(['statusId' => $extract['status_id']]);
                    $userStream->setCreatedAt($extract['created_at']);
                    $userStream->setApiDocument($extract['api_document']);
                    $userStream->setUpdatedAt(new \DateTime());
                } else {
                    /**
                     * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream
                     */
                    $userStream = $this->queryFactory->makeUserStream($extract);
                }

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
                    'hash' => sha1($status->text . $status->id_str),
                    'text' => $status->text,
                    'screen_name' => $status->user->screen_name,
                    'name' => $status->user->name,
                    'user_avatar' => $status->user->profile_image_url,
                    'status_id' => $status->id_str,
                    'api_document' => json_encode($status),
                    'created_at' => new \DateTime($status->created_at),
                ];
                $extract = $setter($extract);
                $extracts[] = $extract;
            }
        }

        return $extracts;
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @param $statusId
     * @param bool $serializedApiDocument
     * @return bool
     */
    public function existsAlready($oauthToken, $screenName, $statusId, $serializedApiDocument = true)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
            ->andWhere('s.identifier = :oauthToken')
            ->andWhere('s.statusId = :statusId')
            ->andWhere('s.screenName = :screenName');

        if ($serializedApiDocument) {
             $queryBuilder->andWhere('s.apiDocument is not null');
         }

        $queryBuilder->setParameter('oauthToken', $oauthToken);
        $queryBuilder->setParameter('screenName', $screenName);
        $queryBuilder->setParameter('statusId', $statusId);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @return mixed
     */
    public function countStatuses($oauthToken, $screenName)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
            ->where('s.identifier = :oauth')
            ->andWhere('s.screenName = :screenName');
        $queryBuilder->setParameter('oauth', $oauthToken);
        $queryBuilder->setParameter('screenName', $screenName);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @return mixed
     */
    public function findNextMaximum($oauthToken, $screenName)
    {
        return $this->findNextExtremum($oauthToken, $screenName, 'asc');
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @return mixed
     */
    public function findNextMininum($oauthToken, $screenName)
    {
        return $this->findNextExtremum($oauthToken, $screenName, 'desc');
    }

    /**
     * @param $oauthToken
     * @param $screenName
     * @param string $direction
     * @return array|mixed
     */
    protected function findNextExtremum($oauthToken, $screenName, $direction = 'asc')
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.identifier = :identifier')
            ->andWhere('s.apiDocument is not null')
            ->orderBy('s.statusId + 0', $direction)
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

    /**
     * @return mixed
     */
    public function createRemainingUserStatusQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->andWhere('s.id > 1031692');

        return $queryBuilder;
    }
}
