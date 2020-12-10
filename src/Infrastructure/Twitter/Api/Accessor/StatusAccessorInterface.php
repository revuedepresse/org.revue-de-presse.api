<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Domain\Collection\CollectionStrategyInterface;
use App\Membership\Entity\MemberInterface;

interface StatusAccessorInterface
{
    public function updateExtremum(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        bool $discoverPublicationWithMaxId = true
    ): array;

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface;

    public function ensureMemberHavingIdExists(string $id): ?MemberInterface;
}