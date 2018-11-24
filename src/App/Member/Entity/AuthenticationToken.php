<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;

class AuthenticationToken
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $token;

    /**
     * @var MemberInterface
     */
    private $member;
}
