<?php
declare (strict_types=1);

namespace App\Search\Domain;

use App\Twitter\Domain\Http\TwitterAPIAwareInterface;

interface SearchQueryAwareHttpClientInterface extends TwitterAPIAwareInterface
{
    public function searchTweets(string $searchQuery);
}
