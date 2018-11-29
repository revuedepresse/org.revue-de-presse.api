<?php

namespace App\Member\Repository;

use App\Member\Authentication\Authenticator;
use App\Member\Entity\AuthenticationToken;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;
use WTW\UserBundle\Repository\UserRepository;

class AuthenticationTokenRepository extends EntityRepository
{
    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @var Authenticator
     */
    public $authenticator;

    /**
     * @param string $tokenId
     * @return MemberInterface
     */
    public function findMemberByTokenId(string $tokenId): MemberInterface
    {
        try {
            $tokenInfo = $this->authenticator->authenticate($tokenId);
        } catch (\Exception $exception) {
            return null;
        }

        /** @var AuthenticationToken $token */
        $token = $this->findOneBy(['token' => $tokenInfo['sub']]);

        return $token->getMember();
    }
}
