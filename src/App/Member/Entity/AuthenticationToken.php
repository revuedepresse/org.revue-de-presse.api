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
     * @return string
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * @var MemberInterface
     */
    private $member;
}
