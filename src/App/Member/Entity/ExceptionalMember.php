<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;
use WTW\UserBundle\Entity\User;

class ExceptionalMember implements MemberInterface
{
    use MemberTrait;
}
