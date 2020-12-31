<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Infrastructure\Security\Authentication\Authenticator;
use App\Membership\Infrastructure\Entity\AuthenticationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Infrastructure\Membership\Repository\MemberRepository;
use const JSON_THROW_ON_ERROR;

class AuthenticationTokenRepository extends ServiceEntityRepository
{
    /**
     * @var MemberRepository
     */
    public MemberRepository $memberRepository;

    /**
     * @var Authenticator
     */
    public Authenticator $authenticator;

    /**
     * @param string $tokenId
     * @return array
     */
    public function findByTokenIdentifier(string $tokenId): array
    {
        try {
            $tokenInfo = $this->authenticator->authenticate($tokenId);
        } catch (\Exception $exception) {
            return [];
        }

        /** @var AuthenticationToken $token */
        $token = $this->findOneBy(['token' => $tokenInfo['sub']]);

        if (!($token instanceof AuthenticationToken)) {
            $defaultMember = new Member();
            $defaultMember->setTwitterScreenName('revue_2_presse');

            return [
                'member' => $defaultMember,
                'granted_routes' => json_encode(['bucket'], JSON_THROW_ON_ERROR),
            ];
        }

        return [
            'member' => $token->getMember(),
            'granted_routes' => $token->getGrantedRoutes()
        ];
    }
}
