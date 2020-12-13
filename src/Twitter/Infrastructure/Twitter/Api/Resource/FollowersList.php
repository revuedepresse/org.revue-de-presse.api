<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Resource;

use InvalidArgumentException;

class FollowersList implements ResourceList
{
    private array $list;

    private string $nextCursor;

    public function __construct(array $list, string $nextCursor)

    {
        $this->list = $list;
        $this->nextCursor = $nextCursor;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function count(): int
    {
        return count($this->list);
    }

    public function nextCursor(): string
    {
        return $this->nextCursor;
    }

    public static function fromResponse(array $response): self
    {
        if (!array_key_exists('users', $response)) {
            throw new InvalidArgumentException('Missing "users" key');
        }

        if (!array_key_exists('next_cursor_str', $response)) {
            throw new InvalidArgumentException('Missing "next_cursor_str" key');
        }

        return new self($response['users'], $response['next_cursor_str']);
    }
}