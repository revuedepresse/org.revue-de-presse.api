<?php

namespace App\Member\Entity;

use App\Membership\Entity\MemberInterface;

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
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @var string
     */
    private $grantedRoutes;

    /**
     * @return string
     */
    public function getGrantedRoutes(): string
    {
        return $this->grantedRoutes;
    }

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @return MemberInterface
     */
    public function getMember(): MemberInterface {
        return $this->member;
    }

}
