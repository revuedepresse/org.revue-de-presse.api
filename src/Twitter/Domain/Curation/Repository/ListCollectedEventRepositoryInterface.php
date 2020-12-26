<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Infrastructure\Curation\Entity\ListCollectedEvent;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;

/**
 * @method ListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListCollectedEvent[]    findAll()
 * @method ListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface ListCollectedEventRepositoryInterface
{
    public function aggregatedLists(
        ListAccessorInterface $accessor,
        string $screenName
    ): ResourceList;

    public function collectedList(
        ListAccessorInterface $accessor,
        ListSelectorInterface $selector
    ): ResourceList;
}