<?php
declare(strict_types=1);

namespace App\Twitter\Domain\PublishersList\Repository;

use App\Twitter\Domain\Publication\PublishersListInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method PublishersListInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublishersListInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublishersListInterface[]    findAll()
 * @method PublishersListInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface PublishersListRepositoryInterface
{
    public function getAllPublishersLists(
        Request $request
    );

    public function make(string $screenName, string $listName);

    public function unlockPublishersList(PublishersListInterface $publishersList);

    public function updateTotalStatuses(
        array $aggregate,
        ?PublishersListInterface $matchingAggregate = null,
        bool $includeRelatedAggregates = true
    ): array;
}