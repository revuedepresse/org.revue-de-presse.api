<?php
declare(strict_types=1);

namespace App\Search\Infrastructure\DependencyInjection;

use App\Search\Domain\SearchQueryAwareHttpClientInterface;

trait SearchQueryAwareHttpClientTrait
{
    private SearchQueryAwareHttpClientInterface $searchQueryAwareHttpClient;

    public function setSearchQueryAwareHttpClient(SearchQueryAwareHttpClientInterface $searchQueryAwareHttpClient): self
    {
        $this->searchQueryAwareHttpClient = $searchQueryAwareHttpClient;

        return $this;
    }
}
