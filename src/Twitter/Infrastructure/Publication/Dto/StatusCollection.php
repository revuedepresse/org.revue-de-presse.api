<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Dto;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Domain\Operation\Collection\StrictCollectionInterface;
use Closure;
use function count;

class StatusCollection implements StrictCollectionInterface
{
    private array $status;

    private function __construct(array $status = [])
    {
        $this->status = array_map(fn(TweetInterface $status) => $status, $status);
    }

    public function count()
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

    public function first(): ?TweetInterface
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->status[0];
    }
}
