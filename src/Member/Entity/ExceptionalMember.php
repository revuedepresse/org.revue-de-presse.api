<?php

namespace App\Member\Entity;

use App\Membership\Entity\MemberInterface;
use App\Membership\Entity\Member;

class ExceptionalMember implements MemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;
}
