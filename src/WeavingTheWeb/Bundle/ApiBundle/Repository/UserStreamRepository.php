<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\NoResultException;
use FOS\ElasticaBundle\Doctrine\ORM\Provider;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class UserStreamRepository
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStreamRepository extends ResourceRepository
{
    /**
     * @var array
     */
    protected $oauthTokens;

    public function setOauthTokens($oauthTokens)
    {
        $this->oauthTokens = $oauthTokens;

        return $this;
    }

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
                /**
                 * Make a distinction between records embedding the actual resource (api_document) and incomplete record
                 * in order to update records which original resources are missing
                 */
                if ($this->existsAlready($extract['identifier'], $extract['screen_name'], $extract['status_id'], false)) {
                    /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository */
                    $userStreamRepository = $entityManager->getRepository('WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream');
                    /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream */
                    $userStream = $userStreamRepository->findOneBy(['statusId' => $extract['status_id']]);
                    $userStream->setCreatedAt($extract['created_at']);
                    $userStream->setApiDocument($extract['api_document']);
                    $userStream->setUpdatedAt(new \DateTime());
                } else {
                    /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream $userStream */
                    $userStream = $this->queryFactory->makeUserStream($extract);
                }

                $userStream->setIndexed(true);
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

        return intval($count);
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
        $alias = Provider::ENTITY_ALIAS;
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->andWhere($alias . '.statusId > 0');
        $queryBuilder->andWhere($alias . '.statusId is not null');
        $queryBuilder->andWhere($alias . '.indexed = :indexed');
        $queryBuilder->setParameter('indexed', false, \PDO::PARAM_BOOL);

        return $queryBuilder;
    }

    /**
     * @param $lastId
     * @return array
     */
    public function findLatest($lastId = null)
    {
        $queryBuilder = $this->selectStatuses();

        if (!is_null($lastId)) {
            $queryBuilder->andWhere('t.id < :lastId');
            $queryBuilder->setParameter('lastId', $lastId);
        }

        $statuses = $queryBuilder->getQuery()->getResult();

        return $this->highlightRetweets($statuses);
    }

    /**
     * @param bool $lastWeek
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function selectStatuses($lastWeek = false)
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select(
            [
                't.userAvatar as author_avatar',
                't.text',
                't.screenName as screen_name',
                't.id',
                't.statusId as status_id',
                't.starred',
                't.apiDocument original_document'
            ]
        )
            ->andWhere('t.identifier IN (:identifier)')
            ->orderBy('t.id', 'desc')
            ->setMaxResults(300)
        ;
        $queryBuilder->setParameter('identifier', $this->oauthTokens);


        if ($lastWeek) {
            $queryBuilder->andWhere('ts.createdAt > :lastWeek');

            $now = new \DateTime();
            $lastWeek = $now->setTimestamp(strtotime('last week'));
            $queryBuilder->setParameter('lastWeek', $lastWeek);
        }

        return $queryBuilder;
    }

    /**
     * @param $statusIds
     * @return mixed
     */
    public function findBookmarks(array $statusIds)
    {
        if (count($statusIds) > 0) {
            $queryBuilder = $this->selectStatuses()
                ->andWhere('t.statusId IN (:statusIds)');
            $queryBuilder->setParameter('statusIds', $statusIds);

            $statuses = $queryBuilder->getQuery()->getResult();

            return $this->highlightRetweets($statuses);
        } else {
            return [];
        }
    }

    /**
     * @param array $statuses
     * @return mixed
     */
    protected function highlightRetweets(array $statuses)
    {
        array_walk(
            $statuses,
            function (&$status) {
                $target = sprintf('user stream of id #%d', intval($status['id']));
                if (strlen($status['original_document']) > 0) {
                    $decodedValue = json_decode($status['original_document'], true);
                    $lastJsonError = json_last_error();
                    if (JSON_ERROR_NONE === $lastJsonError) {
                        if (array_key_exists('retweeted_status', $decodedValue)) {
                            $status['text'] = 'RT @' . $decodedValue['retweeted_status']['user']['screen_name'] . ': ' .
                                $decodedValue['retweeted_status']['text'];
                        }
                    } else {
                        throw new \Exception(sprintf($lastJsonError . ' affecting ' . $target));
                    }
                } else {
                    throw new \Exception(sprintf('Empty JSON document for ' . $target));
                }
            }
        );

        return $statuses;
    }
}
