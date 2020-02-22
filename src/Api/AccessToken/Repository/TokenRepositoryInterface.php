<?php
declare(strict_types=1);

namespace App\Api\AccessToken\Repository;

use App\Api\Entity\TokenInterface;

interface TokenRepositoryInterface
{
    public function findTokenOtherThan(string $token): ?TokenInterface;

    public function howManyUnfrozenTokenAreThere(): int;
}