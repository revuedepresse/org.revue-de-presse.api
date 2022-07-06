<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Http;

use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;

trait TweetAwareHttpClientTrait
{
    private TweetAwareHttpClientInterface $tweetAwareHttpClient;

    public function setTweetAwareHttpClient(TweetAwareHttpClientInterface $tweetAwareHttpClient): self
    {
        $this->tweetAwareHttpClient = $tweetAwareHttpClient;

        return $this;
    }
}