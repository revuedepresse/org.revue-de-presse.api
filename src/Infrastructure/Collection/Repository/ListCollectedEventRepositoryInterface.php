<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\FriendsListCollectedEvent;
use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\ResourceList;

/**
 * @method FriendsListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendsListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendsListCollectedEvent[]    findAll()
 * @method FriendsListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface ListCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_CURSOR = 'cursor';

    public function aggregatedLists(
        ListAccessorInterface $accessor,
        string $screenName
    ): ResourceList;

    public function collectedList(
        ListAccessorInterface $accessor,
        array $options
    ): ResourceList;
}