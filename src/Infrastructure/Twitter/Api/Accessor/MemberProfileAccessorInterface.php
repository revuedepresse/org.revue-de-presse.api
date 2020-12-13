<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Domain\Resource\MemberIdentity;
use App\Membership\Domain\Entity\MemberInterface;

interface MemberProfileAccessorInterface extends ExceptionalMemberInterface
{
    public function getMemberByIdentity(MemberIdentity $memberIdentity): MemberInterface;
}