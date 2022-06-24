<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Membership\Domain\Exception\ExceptionalMemberInterface;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;

interface MemberProfileAwareHttpClientInterface extends ExceptionalMemberInterface
{
    public function getMemberByIdentity(MemberIdentity $memberIdentity): MemberInterface;
}