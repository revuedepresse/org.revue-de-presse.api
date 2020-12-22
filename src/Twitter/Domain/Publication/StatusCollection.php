<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use App\Twitter\Infrastructure\Operation\Collection\StrictCollectionInterface;
use Closure;
use function count;

class StatusCollection implements StrictCollectionInterface
{
    private array $status;

    private function __construct(array $status = [])
    {
        $this->status = array_map(fn(StatusInterface $status) => $status, $status);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->status);
    }

    public function map(Closure $callable): StrictCollectionInterface
    {
        return self::fromArray(array_map(
            $callable,
            $this->status
        ));
    }

    public function add($status): self
    {
        $this->status[] = $status;

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->status) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $status): self
    {
        return new self($status);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->status;
    }

    public function first(): ?StatusInterface
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->status[0];
    }
}