<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\AccessToken\Repository;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Api\Security\Authorization\AccessTokenInterface;

/**
 * @method TokenInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInterface[]    findAll()
 * @method TokenInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface TokenRepositoryInterface
{
    public function findByUserToken(string $userToken): TokenInterface;

    public function findTokenOtherThan(string $token): ?TokenInterface;

    public function findFirstUnfrozenToken(): ?TokenInterface;

    public function findFirstFrozenToken(): ?TokenInterface;

    public function howManyUnfrozenTokenAreThere(): int;

    public function howManyUnfrozenTokenAreThereExceptFrom(TokenInterface $excludedToken): int;

    public function ensureAccessTokenExists(
        string $oauthToken,
        string $oauthTokenSecret,
        string $consumerKey,
        string $consumerSecret
    ): void;

    public function freezeToken(TokenInterface $oauthToken): void;

    public function saveAccessToken(AccessTokenInterface $accessToken): TokenInterface;
}