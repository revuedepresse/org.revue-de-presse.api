<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Api;

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