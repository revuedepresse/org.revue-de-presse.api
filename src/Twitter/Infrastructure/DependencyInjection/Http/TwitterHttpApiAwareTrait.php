<?php

namespace App\Twitter\Infrastructure\DependencyInjection\Http;

trait TwitterHttpApiAwareTrait
{
    public const BASE_URL = 'https://api.twitter.com/';

    public const TWITTER_API_VERSION_1_1 = '1.1';

    public const TWITTER_API_VERSION_2 = '2';

    protected string $apiHost = 'api.twitter.com';

    public function getApiBaseUrl(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return 'https://' . $this->apiHost . '/' . $version;
    }
}