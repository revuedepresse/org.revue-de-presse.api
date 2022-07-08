<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Api;

use App\Twitter\Domain\Http\ApiAccessorInterface;

trait ApiAccessorTrait
{
    protected ApiAccessorInterface $apiAccessor;

    public function setApiAccessor(ApiAccessorInterface $apiAccessor): self
    {
        $this->apiAccessor = $apiAccessor;

        return $this;
    }
}
