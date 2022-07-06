<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Entity;

trait TokenTrait
{
    public function toArray(): array
    {
        return [
            'token' => $this->getAccessToken(),
            'secret' => $this->getAccessTokenSecret(),
            'consumer_token' => $this->getConsumerKey(),
            'consumer_secret' => $this->getConsumerSecret()
        ];
    }
}