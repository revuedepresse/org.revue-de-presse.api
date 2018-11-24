<?php

namespace App\Aggregate\Controller;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;

class ListController
{
    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    public function getLists(Request $request)
    {
        $pageIndex = intval($request->get('page_index', 1));
        $pageSize = intval($request->get('page_size', 25));

        $totalPages = $this->countTotalPages($pageSize);

        $totalPagesHeader = ['total-pages' => $totalPages];

        if ($pageIndex > $totalPages) {
            $response = new JsonResponse([]);
            $response->headers->add($totalPagesHeader);

            return $response;
        }

        $queryBuilder = $this->aggregateRepository->createQueryBuilder('a');
        $this->applyCriteria($queryBuilder);

        $queryBuilder->setFirstResult(($pageIndex - 1) * $pageSize);
        $queryBuilder->setMaxResults($pageSize);

        $aggregates = $queryBuilder->getQuery()->getArrayResult();

        $response = new JsonResponse($aggregates);
        $response->headers->add($totalPagesHeader);

        return $response;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    private function applyCriteria(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->andWhere('a.screenName IS NULL');
        $queryBuilder->andWhere('a.name not like :name');
        $queryBuilder->setParameter('name', "user ::%");
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function countTotalPages($pageSize): int
    {
        $queryBuilder = $this->aggregateRepository->createQueryBuilder('a');
        $queryBuilder->select('count(a.id) total_lists');
        $this->applyCriteria($queryBuilder);
        $result = $queryBuilder->getQuery()->getSingleResult();

        return ceil($result['total_lists'] / $pageSize);
    }
}
