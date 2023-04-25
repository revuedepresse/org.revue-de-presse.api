<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

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
    public function allPublishersLists(Request $request): array;

    public function byName(
        string $screenName,
        string $listName,
        ?string $listId = null
    ): PublishersListInterface;

    public function make(string $screenName, string $listName);

    public function unlockPublishersList(PublishersListInterface $twitterList);

    public function updateTotalStatuses(
        array $aggregate,
        ?PublishersListInterface $matchingAggregate = null,
        bool $includeRelatedAggregates = true
    ): array;
}
