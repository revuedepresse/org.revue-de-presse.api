<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Membership\Domain\Model\MemberInterface;

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