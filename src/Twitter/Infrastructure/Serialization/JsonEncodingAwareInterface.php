<?php

namespace App\Twitter\Infrastructure\Serialization;

interface JsonEncodingAwareInterface
{
    public function encodeAsJson(): string;
}
