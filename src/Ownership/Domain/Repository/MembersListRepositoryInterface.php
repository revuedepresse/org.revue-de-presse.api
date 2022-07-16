<?php
declare(strict_types=1);

namespace App\Ownership\Domain\Repository;

use App\Ownership\Domain\Entity\MembersListInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method MembersListInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MembersListInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MembersListInterface[]    findAll()
 * @method MembersListInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface MembersListRepositoryInterface
{
    public function getAllPublishersLists(
        Request $request
    );

    public function make(string $screenName, string $listName);

    public function unlockPublishersList(MembersListInterface $publishersList);

    public function updateTotalStatuses(
        array                 $list,
        ?MembersListInterface $matchingAggregate = null,
        bool                  $includeRelatedAggregates = true
    ): array;
}
