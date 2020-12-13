<?php
declare(strict_types=1);

namespace App\Domain\Publication\Repository;

use App\Domain\Publication\StatusInterface;
use App\Domain\Publication\TaggedStatus;
use Doctrine\Persistence\ObjectRepository;

interface StatusRepositoryInterface extends ObjectRepository, ExtremumAwareInterface
{
    public function findNextExtremum(
        string $screenName,
        string $direction = ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array;

    public function reviseDocument(TaggedStatus $taggedStatus): StatusInterface;
}