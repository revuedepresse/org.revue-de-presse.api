<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Resource;

use App\Twitter\Domain\Api\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Operation\Collection\StrictCollectionInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use Closure;
use function count;

class MemberCollection implements MemberCollectionInterface
{
    private array $members;

    public function __construct(array $members)
    {
        $this->members = array_map(
            function ($member) {
                if ($member instanceof MemberIdentity) {
                    return $member;
                }

                return new MemberIdentity(
                    $member->screen_name,
                    $member->id_str
                );
            },
            $members
        );
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->members);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function map(Closure $callable): StrictCollectionInterface
    {
        return self::fromArray(array_map(
            $callable,
            $this->members,
        ));
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $members): StrictCollectionInterface
    {
        return new self($members);
    }

    /**
     * @return array<MemberIdentity>
     *
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->members;
    }

    /**
     * @return int|null
     */
    public function first(): ?int
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->members[0];
    }

    public function add($member): self
    {
        $this->members[] = $member;

        return $this;
    }
}