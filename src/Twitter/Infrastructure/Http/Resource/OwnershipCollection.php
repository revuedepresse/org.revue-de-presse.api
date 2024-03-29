<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Resource;

use Closure;
use function count;

class OwnershipCollection implements OwnershipCollectionInterface
{
    private array $ownerships;

    private int $nextPage;

    private function __construct(array $ownerships, int $nextPage)
    {
        $this->nextPage = $nextPage;
        $this->ownerships = array_map(
            function ($ownership) {
                if ($ownership instanceof PublishersList) {
                    return $ownership;
                }

                return new PublishersList(
                    $ownership->id_str,
                    $ownership->name
                );
            },
            $ownerships
        );
    }

    public function goBackToFirstPage(): self
    {
        $this->nextPage = -1;

        return $this;
    }

    public function map(Closure $callable): OwnershipCollectionInterface
    {
        return self::fromArray(array_map($callable, $this->ownerships));
    }

    public static function fromArray(array $ownerships, int $nextPage = -1): OwnershipCollectionInterface
    {
        return new self($ownerships, $nextPage);
    }

    public function count(): int
    {
        return count($this->ownerships);
    }

    public function isEmpty(): bool
    {
        return count($this->ownerships) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function toArray(): array
    {
        return $this->ownerships;
    }

    public function nextPage(): int
    {
        return $this->nextPage;
    }

    public function first(): PublishersList
    {
        return $this->ownerships[0];
    }

    public function add($ownership): self
    {
        $this->ownerships[] = $ownership;

        return $this;
    }
}
