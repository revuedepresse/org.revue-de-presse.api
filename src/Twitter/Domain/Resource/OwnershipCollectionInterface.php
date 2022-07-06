<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

use App\Twitter\Domain\Operation\Collection\StrictCollectionInterface;

interface OwnershipCollectionInterface extends StrictCollectionInterface
{
    public function goBackToFirstPage(): self;

    public static function fromArray(array $ownerships, int $nextPage = -1): self;

    public function nextPage(): int;

    public function first(): PublishersList;

    public function add($ownership): self;
}