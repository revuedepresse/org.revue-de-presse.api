<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Operation\Collection\Collection;
use App\Operation\Collection\CollectionInterface;

interface PublicationRepositoryInterface
{
    public function getLatestPublications(): Collection;

    public function persistPublications(CollectionInterface $collection): CollectionInterface;
}