<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Membership;

use App\Domain\Resource\MemberIdentity;
use App\Membership\Domain\Entity\MemberInterface;

interface MemberRepositoryInterface
{
    public function declareMemberAsFound(MemberInterface $member): MemberInterface;

    public function declareMemberAsNotFound(MemberInterface $member): MemberInterface;

    public function findOneBy(array $criteria, ?array $orderBy = null);

    public function saveMemberFromIdentity(MemberIdentity $memberIdentity): MemberInterface;

    public function saveProtectedMember(MemberIdentity $memberIdentity): MemberInterface;

    public function saveSuspendedMember(MemberIdentity $memberIdentity): MemberInterface;

    public function getMinPublicationIdForMemberHavingScreenName(string $screenName): ?int;

    public function hasBeenUpdatedBetween7HoursAgoAndNow(string $screenName): bool;
}