<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\PublicationList;

use App\Domain\Publication\PublicationListInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method PublicationListInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublicationListInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublicationListInterface[]    findAll()
 * @method PublicationListInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface PublicationListRepositoryInterface
{
    public function getAllPublicationLists(
        Request $request
    );

    public function make(string $screenName, string $listName);

    public function unlockAggregate(PublicationListInterface $publicationList);

    public function updateTotalStatuses(
        array $aggregate,
        ?PublicationListInterface $matchingAggregate = null,
        bool $includeRelatedAggregates = true
    ): array;
}