<?php
declare(strict_types=1);

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;

interface MemberRepositoryInterface
{
    public function declareMemberAsFound(MemberInterface $member): MemberInterface;

    public function declareMemberAsNotFound(MemberInterface $member): MemberInterface;

    public function findOneBy(array $criteria, ?array $orderBy = null);

    public function memberHavingScreenName(string $screenName): MemberInterface;

    public function saveMember(MemberInterface $member): MemberInterface;

    public function saveApiConsumer(MemberIdentity $memberIdentity, string $apiKey): MemberInterface;

    public function saveMemberFromIdentity(MemberIdentity $memberIdentity): MemberInterface;

    public function saveProtectedMember(MemberIdentity $memberIdentity): MemberInterface;

    public function saveSuspendedMember(MemberIdentity $memberIdentity): MemberInterface;

    public function getMinPublicationIdForMemberHavingScreenName(string $screenName): ?int;

    public function hasBeenUpdatedBetweenHalfAnHourAgoAndNow(string $screenName): bool;
}
