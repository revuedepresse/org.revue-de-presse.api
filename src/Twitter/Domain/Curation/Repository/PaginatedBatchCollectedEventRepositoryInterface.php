<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\ListCollectedEvent;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Domain\Http\Resource\ResourceList;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;

/**
 * @method ListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListCollectedEvent[]    findAll()
 * @method ListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface PaginatedBatchCollectedEventRepositoryInterface
{
    public function aggregatedLists(
        CursorAwareHttpClientInterface $accessor,
        string $screenName
    ): ResourceList;

    public function collectedList(
        CursorAwareHttpClientInterface $accessor,
        ListSelectorInterface $selector
    ): ResourceList;
}