<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Selector;

use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;

class FriendsListSelector implements ListSelectorInterface, CorrelationIdAwareInterface
{
    private CorrelationIdInterface $correlationId;
    private string $screenName;
    private string $cursor;

    public function __construct(
        string $screenName,
        string $cursor = '-1',
        CorrelationIdInterface $correlationId = null
    ) {
        $this->screenName = $screenName;
        $this->cursor = $cursor;

        if ($correlationId === null) {
            $correlationId = CorrelationId::generate();
        }

        $this->correlationId = $correlationId;
    }

    public function correlationId(): CorrelationIdInterface
    {
        return $this->correlationId;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function cursor(): string
    {
        return $this->cursor;
    }

    public function isDefaultCursor(): bool
    {
        return $this->cursor === self::DEFAULT_CURSOR;
    }
}