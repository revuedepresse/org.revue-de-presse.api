<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Membership\Domain\Exception\ExceptionalMemberInterface;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;

interface MemberProfileAccessorInterface extends ExceptionalMemberInterface
{
    public function getMemberByIdentity(MemberIdentity $memberIdentity): MemberInterface;
}