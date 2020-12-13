<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Membership\Domain\Entity\MemberInterface;

interface MemberProfileAccessorInterface extends ExceptionalMemberInterface
{
    public function getMemberByIdentity(MemberIdentity $memberIdentity): MemberInterface;
}