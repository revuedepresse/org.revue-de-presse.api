<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Ownership\Domain\Entity\MembersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;

interface TaggedStatusRepositoryInterface
{
    public function convertPropsToStatus(
        array $properties,
        ?MembersListInterface $list
    ): StatusInterface;

    public function archivedStatusHavingHashExists(string $hash): bool;

    public function statusHavingHashExists(string $hash): bool;
}
