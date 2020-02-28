<?php
declare(strict_types=1);

namespace App\Domain\Collection;

interface CollectionStrategyInterface
{
    public function dateBeforeWhichStatusAreToBeCollected(): ?string;

    public function publicationListId(): ?int;

    public function fetchLikes(): bool;

    public function oneOfTheOptionsIsActive(): bool;

    public function optInToCollectStatusPublishedBefore(string $date): self;

    public function optInToCollectStatusForPublicationListOfId(
        ?int $publicationList = null
    ): self;

    public function optInToFetchLikes(bool $fetchLikes = false): self;

    public static function fromArray(array $options): self;
}