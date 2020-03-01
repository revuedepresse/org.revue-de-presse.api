<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Status;

use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Status\Repository\ExtremumAwareInterface;
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