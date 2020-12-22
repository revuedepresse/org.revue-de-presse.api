<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\OwnershipAccessorInterface;

trait OwnershipAccessorTrait
{
    private OwnershipAccessorInterface $ownershipAccessor;

    public function setOwnershipAccessor(OwnershipAccessorInterface $ownershipAccessor): self
    {
        $this->ownershipAccessor = $ownershipAccessor;

        return $this;
    }
}