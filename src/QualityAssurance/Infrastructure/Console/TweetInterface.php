<?php

namespace App\QualityAssurance\Infrastructure\Console;

interface TweetInterface
{
    public function hasBeenDeleted(): bool;

    public function markAsDeleted(): self;

    public function rawDocument(): array;

    public function tweetId(): string;

    public function overrideProperties(array $overrides): self;
}