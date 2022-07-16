<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use App\Ownership\Domain\Entity\MembersList;
use App\Ownership\Domain\Entity\MembersListInterface;

class Tag
{
    private MembersListInterface $tag;

    private function __construct(MembersListInterface $tag)
    {
        $this->tag = $tag;
    }

    public function fromAggregate(MembersListInterface $tag): self
    {
        return new self($tag);
    }

    public function name(): string
    {
        return $this->tag->getName();
    }

    public function tag(): MembersList
    {
        return $this->tag;
    }
}
