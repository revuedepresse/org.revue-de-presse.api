<?php
declare(strict_types=1);

namespace App\Serialization;

interface JsonEncodingAwareInterface
{
    public function encodeAsJson(): string;
}
