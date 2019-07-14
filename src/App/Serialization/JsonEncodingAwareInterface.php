<?php

namespace App\Serialization;

interface JsonEncodingAwareInterface
{
    public function encodeAsJson(): string;
}
