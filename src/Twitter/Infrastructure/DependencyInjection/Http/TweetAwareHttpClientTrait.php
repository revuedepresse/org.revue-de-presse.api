<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Http;

use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;

trait TweetAwareHttpClientTrait
{
    private TweetAwareHttpClientInterface $statusAccessor;

    public function setTweetAwareHttpClient(TweetAwareHttpClientInterface $statusAccessor): self
    {
        $this->statusAccessor = $statusAccessor;

        return $this;
    }
}