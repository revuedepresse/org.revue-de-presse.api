<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;

trait OwnershipAccessorTrait
{
    private ListAwareHttpClientInterface $ownershipAccessor;

    public function setOwnershipAccessor(ListAwareHttpClientInterface $ownershipAccessor): self
    {
        $this->ownershipAccessor = $ownershipAccessor;

        return $this;
    }
}