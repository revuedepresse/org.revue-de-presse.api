<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Membership\Domain\Model\MemberInterface;

interface TweetAwareHttpClientInterface
{
    public function updateExtremum(
        CurationSelectorsInterface $selectors,
        array                      $options,
        bool                       $discoverPublicationWithMaxId = true
    ): array;

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface;

    public function ensureMemberHavingIdExists(string $id): ?MemberInterface;
}