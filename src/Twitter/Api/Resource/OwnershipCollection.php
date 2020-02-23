<?php
declare(strict_types=1);

namespace App\Twitter\Api\Resource;

use App\Operation\Collection\StrictCollectionInterface;
use Closure;
use function count;

class OwnershipCollection implements StrictCollectionInterface
{
    /**
     * @var array
     */
    private array $list;

    private int $nextPage;

    private function __construct(array $list, int $nextPage)
    {
        $this->list = $list;
        $this->nextPage = $nextPage;
    }

    public function goBackToFirstPage(): self
    {
        $this->nextPage = -1;

        return $this;
    }

    public function map(Closure $closure): array
    {
        return array_map($closure, $this->list);
    }

    public static function fromArray(array $list, int $nextPage = -1): self
    {
        return new self($list, $nextPage);
    }

    public function count(): int
    {
        return count($this->list);
    }

    public function isEmpty(): bool
    {
        return count($this->list) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function toArray(): array
    {
        return $this->list;
    }

    public function nextPage(): int
    {
        return $this->nextPage;
    }
}