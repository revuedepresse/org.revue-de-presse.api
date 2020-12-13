<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\AccessToken\Repository;

use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use Psr\Log\LoggerInterface;

/**
 * @method TokenInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInterface[]    findAll()
 * @method TokenInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface TokenRepositoryInterface
{
    public function findTokenOtherThan(string $token): ?TokenInterface;

    public function findFirstUnfrozenToken(): ?TokenInterface;

    public function findFirstFrozenToken(): ?TokenInterface;

    public function howManyUnfrozenTokenAreThere(): int;

    public function ensureTokenExists(
        string $oauthToken,
        string $oauthTokenSecret,
        string $consumerKey,
        string $consumerSecret
    ): void;

    public function refreshFreezeCondition(
        string $oauthToken,
        LoggerInterface $logger
    ): TokenInterface;
}