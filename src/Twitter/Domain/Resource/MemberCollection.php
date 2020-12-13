<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Resource;

use App\Twitter\Infrastructure\Operation\Collection\StrictCollectionInterface;
use Closure;
use function count;

class MemberCollection implements StrictCollectionInterface
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