<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;
use App\Member\Entity\Member;

class ExceptionalMember implements MemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;
}
