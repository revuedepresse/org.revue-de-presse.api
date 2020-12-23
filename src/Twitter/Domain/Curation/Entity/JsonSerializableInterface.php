<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use JsonSerializable;

interface JsonSerializableInterface extends JsonSerializable
{
    public function jsonSerialize(): string;

    public static function jsonDeserialize(string $serializedSubject): self;
}