<?php
declare(strict_types=1);

namespace App\Amqp\Message;

/**
 * @package App\Amqp\Message
 */
class FetchMemberStatuses
{
    /**
     * @var string
     */
    private string $screenName;

    /**
     * @var int
     */
    private int $aggregateId;

    /**
     * @var string
     */
    private ?string $before;

    /**
     * @param string      $screenName
     * @param int         $aggregateId
     * @param string|null $before
     */
    public function __construct(
        string $screenName,
        int $aggregateId,
        ?string $before = null
    ) {

        $this->screenName = $screenName;
        $this->aggregateId = $aggregateId;
        $this->before = $before;
    }

    /**
     * @return string
     */
    public function screenName(): string
    {
        return $this->screenName;
    }

    /**
     * @return int
     */
    public function aggregateId(): int
    {
        return $this->aggregateId;
    }

    /**
     * @return string|null
     */
    public function before(): ?string
    {
        return $this->before;
    }
}