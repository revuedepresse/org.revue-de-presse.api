<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Domain\Http\Model\TokenInterface;

class FetchSearchQueryMatchingTweet implements FetchSearchQueryMatchingTweetInterface
{
    private string $searchQuery;

    private TokenInterface $token;

    private ?bool $dateBeforeWhichStatusAreCollected;

    public function __construct(
        string         $searchQuery,
        TokenInterface $token,
        ?string        $dateBeforeWhichStatusAreCollected = null
    ) {
        $this->searchQuery = $searchQuery;
        $this->dateBeforeWhichStatusAreCollected = $dateBeforeWhichStatusAreCollected;
        $this->token = $token;
    }

    public function dateBeforeWhichStatusAreCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    public function token(): TokenInterface
    {
        return $this->token;
    }

    public function searchQuery(): string
    {
        return $this->searchQuery;
    }

    public static function matchWithSearchQuery(
        string $searchQuery,
        TokenInterface $token,
        ?string $dateBeforeWhichStatusAreCollected
    ): FetchSearchQueryMatchingTweetInterface {
        return new self(
            $searchQuery,
            $token,
            $dateBeforeWhichStatusAreCollected
        );
    }
}

