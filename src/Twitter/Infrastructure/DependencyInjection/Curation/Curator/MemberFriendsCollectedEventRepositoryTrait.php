<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Curator;

use App\Twitter\Domain\Curation\Repository\MemberFriendsCollectedEventRepositoryInterface;

/**
 * See [official terminology](https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/follow-search-get-users/overview)
 *
 * Here is a excerpt from the documentation (quote from the 24th of June 2022)
 *
 * To avoid confusion around the term "friends" and "followers" with respect to the API endpoints,
 * below is a definition of each:
 *
 * Friends - we refer to "friends" as the Twitter users that a specific user follows (e.g., following).
 * Therefore, the GET friends/ids endpoint returns a collection of user IDs that the specified user follows.
 *
 * Followers - refers to the Twitter users that follow a specific user.
 * Therefore, making a request to the GET followers/ids endpoint returns a collection of user IDs
 * for every user following the specified user.
 */
trait MemberFriendsCollectedEventRepositoryTrait
{
    private MemberFriendsCollectedEventRepositoryInterface $memberFriendsCollectedEventRepository;

    public function setMemberFriendsCollectedEventRepository(
        MemberFriendsCollectedEventRepositoryInterface $memberFriendsCollectedEventRepository
    ): self {
        $this->memberFriendsCollectedEventRepository = $memberFriendsCollectedEventRepository;

        return $this;
    }
}