<?php


namespace App\Infrastructure\DependencyInjection;

use App\Twitter\Api\ApiAccessorInterface;

trait ApiAccessorTrait
{
    protected ApiAccessorInterface $apiAccessor;

    public function setApiAccessor(ApiAccessorInterface $apiAccessor): self
    {
        $this->apiAccessor = $apiAccessor;

        return $this;
    }
}