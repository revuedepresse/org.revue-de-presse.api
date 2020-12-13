<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Infrastructure\Operation\Collection\Collection;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;

interface PublicationRepositoryInterface
{
    public function getLatestPublications(): Collection;

    public function persistPublications(CollectionInterface $collection): CollectionInterface;
}