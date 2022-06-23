<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Api;

use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;

trait ApiAccessorTrait
{
    protected ApiAccessorInterface $apiAccessor;

    public function setApiAccessor(ApiAccessorInterface $apiAccessor): self
    {
        $this->apiAccessor = $apiAccessor;

        return $this;
    }
}