<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Http;

use App\Twitter\Domain\Http\Client\HttpClientInterface;

trait HttpClientTrait
{
    protected HttpClientInterface $apiClient;

    public function setHttpClient(HttpClientInterface $apiClient): self
    {
        $this->apiClient = $apiClient;

        return $this;
    }
}