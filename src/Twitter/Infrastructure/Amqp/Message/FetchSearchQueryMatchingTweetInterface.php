<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Domain\Http\Model\TokenInterface;

interface FetchSearchQueryMatchingTweetInterface extends FetchTweetInterface
{
    public const SEARCH_QUERY = 'search_query';

    public function searchQuery(): string;

    public static function matchWithSearchQuery(
        string          $searchQuery,
        TokenInterface  $token,
        ?string         $dateBeforeWhichStatusAreCollected
    ): self;
}
