<?php
declare(strict_types=1);

namespace App\Amqp\Message;

/**
 * @package App\Amqp\Message
 */
class FetchMemberStatuses
{
    public const AGGREGATE_ID = 'aggregate_id';
    public const SCREEN_NAME = 'screen_name';
    public const BEFORE = 'before';

    /**
     * @var string
     */
    private string $screenName;

    /**
     * @var int
     */
    private int $aggregateId;

    /**
     * @var array $credentials
     */
    private array $credentials;

    /**
     * @var string
     */
    private ?string $before;

    /**
     * @param string      $screenName
     * @param int         $aggregateId
     * @param array       $credentials
     * @param string|null $before
     */
    public function __construct(
        string $screenName,
        int $aggregateId,
        array $credentials,
        ?string $before = null
    ) {

        $this->screenName = $screenName;
        $this->aggregateId = $aggregateId;
        $this->before = $before;
        $this->credentials = $credentials;
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

    /**
     * @return array
     */
    public function credentials(): array
    {
        return $this->credentials;
    }
}