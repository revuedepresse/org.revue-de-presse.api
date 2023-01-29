<?php

namespace App\QualityAssurance\Infrastructure\HttpClient;

use App\QualityAssurance\Domain\HttpClient\HttpClientInterface;

class HttpClient implements HttpClientInterface
{
    public static function getContents(string $url): bool|string
    {
        return file_get_contents($url);
    }
}