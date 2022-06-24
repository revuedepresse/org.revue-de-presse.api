<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Http\Client\HttpClientInterface;

interface MemberFriendsCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';

    public function collectedMemberFriends(
        HttpClientInterface $accessor,
        array $options
    );
}