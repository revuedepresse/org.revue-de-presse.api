<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Twitter\Api\OwnershipAccessorInterface;

trait OwnershipAccessorTrait
{
    private OwnershipAccessorInterface $ownershipAccessor;

    public function setOwnershipAccessor(OwnershipAccessorInterface $ownershipAccessor)
    {
        $this->ownershipAccessor = $ownershipAccessor;
    }
}