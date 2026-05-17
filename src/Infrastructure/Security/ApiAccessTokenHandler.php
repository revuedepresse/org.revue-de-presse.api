<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly AccessTokenStore $store,
        private readonly EntityRepository $memberRepository,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $record = $this->store->resolve($accessToken);
        if ($record === null) {
            throw new BadCredentialsException('Invalid or expired access token.');
        }

        return new UserBadge(
            $record->memberId,
            fn(string $id): ?Member => $this->memberRepository->find((int) $id),
        );
    }
}
