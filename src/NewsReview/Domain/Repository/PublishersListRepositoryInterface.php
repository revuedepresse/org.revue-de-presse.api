<?php
declare (strict_types=1);

namespace App\NewsReview\Domain\Repository;

use App\NewsReview\Domain\Routing\Model\PublishersListInterface;

interface PublishersListRepositoryInterface
{
    public function findByName(string $name): PublishersListInterface;
}