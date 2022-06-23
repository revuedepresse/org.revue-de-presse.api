<?php
declare (strict_types=1);

namespace App\Tests\Membership\Builder\Entity\Legacy;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;

class MemberBuilder
{
    public static function build(string $screenName, string $twitterId = '99'): MemberInterface
    {
        return (new Member())
            ->setTwitterID($twitterId)
            ->setUsername($screenName)
            ->setTwitterScreenName($screenName)
            ->setEmail('@' . $screenName);
    }
}