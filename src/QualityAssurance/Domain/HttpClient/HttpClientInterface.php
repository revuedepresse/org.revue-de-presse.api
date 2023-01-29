<?php

namespace App\QualityAssurance\Domain\HttpClient;

interface HttpClientInterface
{
    public static function getContents(string $url): bool|string;
}