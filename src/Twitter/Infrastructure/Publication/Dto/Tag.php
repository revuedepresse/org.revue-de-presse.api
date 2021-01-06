<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Dto;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;

class Tag
{
    private PublishersList $tag;

    private function __construct(PublishersList $tag)
    {
        $this->tag = $tag;
    }

    public function fromAggregate(PublishersList $tag): self
    {
        return new self($tag);
    }

    public function name(): string
    {
        return $this->tag->name();
    }

    public function tag(): PublishersList
    {
        return $this->tag;
    }
}