<?php
declare(strict_types=1);

namespace App\Api\Entity;

trait TokenTrait
{
    public function toArray(): array
    {
        return [
            'token' => $this->getOAuthToken(),
            'secret' => $this->getOAuthSecret(),
            'consumer_token' => $this->getConsumerKey(),
            'consumer_secret' => $this->getConsumerSecret()
        ];
    }
}