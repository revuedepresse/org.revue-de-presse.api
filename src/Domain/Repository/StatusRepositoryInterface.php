<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;

interface StatusRepositoryInterface
{
    public function reviseDocument(TaggedStatus $taggedStatus): StatusInterface;

}