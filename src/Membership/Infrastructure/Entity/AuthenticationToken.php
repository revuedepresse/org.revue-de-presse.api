<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;

class AuthenticationToken
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $token;

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
    private string $grantedRoutes;

    /**
     * @return string
     */
    public function getGrantedRoutes(): string
    {
        return $this->grantedRoutes;
    }

    private MemberInterface $member;

    public function getMember(): MemberInterface {
        return $this->member;
    }

}
