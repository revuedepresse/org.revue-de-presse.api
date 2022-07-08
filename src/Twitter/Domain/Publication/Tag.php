<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;

class Tag
{
    private PublishersListInterface $tag;

    private function __construct(PublishersListInterface $tag)
    {
        $this->tag = $tag;
    }

    public function fromAggregate(PublishersListInterface $tag): self
    {
        return new self($tag);
    }

    public function name(): string
    {
        return $this->tag->getName();
    }

    public function tag(): PublishersList
    {
        return $this->tag;
    }
}
