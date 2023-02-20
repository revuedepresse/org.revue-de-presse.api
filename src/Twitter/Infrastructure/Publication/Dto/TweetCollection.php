<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Dto;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Domain\Operation\Collection\StrictCollectionInterface;
use Closure;
use function count;

class TweetCollection implements StrictCollectionInterface
{
    private array $tweet;

    private function __construct(array $tweet = [])
    {
        $this->tweet = array_map(fn(TweetInterface $tweet) => $tweet, $tweet);
    }

    public function count(): int
    {
        return count($this->tweet);
    }

    public function map(Closure $callable): StrictCollectionInterface
    {
        return self::fromArray(array_map(
            $callable,
            $this->tweet
        ));
    }

    public function add($item): self
    {
        $this->tweet[] = $item;

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->tweet) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $collection): self
    {
        return new self($collection);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->tweet;
    }

    public function first(): ?TweetInterface
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->tweet[0];
    }
}
