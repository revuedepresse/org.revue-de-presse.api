<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface PublicationRepositoryInterface
{
    public function getLatestPublications(): CollectionInterface;

    public function persistPublications(CollectionInterface $collection): CollectionInterface;
}