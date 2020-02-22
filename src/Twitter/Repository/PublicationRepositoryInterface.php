<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Operation\Collection\CollectionInterface;

interface PublicationRepositoryInterface
{
    public function persistPublications(CollectionInterface $collection): CollectionInterface;
}