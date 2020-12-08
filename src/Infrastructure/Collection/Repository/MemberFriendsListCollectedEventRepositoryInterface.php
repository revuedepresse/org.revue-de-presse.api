<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\MemberFriendsListCollectedEvent;
use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\FriendsList;

/**
 * @method MemberFriendsListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberFriendsListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberFriendsListCollectedEvent[]    findAll()
 * @method MemberFriendsListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface MemberFriendsListCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_CURSOR = 'cursor';

    public function aggregatedMemberFriendsLists(
        FriendsAccessorInterface $accessor,
        string $screenName
    ): FriendsList;

    public function collectedMemberFriendsList(
        FriendsAccessorInterface $accessor,
        array $options
    ): FriendsList;
}