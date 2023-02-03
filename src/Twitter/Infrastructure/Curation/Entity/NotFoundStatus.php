<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\TweetInterface;
use Ramsey\Uuid\UuidInterface;

class NotFoundStatus
{
    private UuidInterface $id;

    private ?Tweet $status = null;

    private ?ArchivedTweet $archivedStatus = null;

    public function __construct(Tweet $status = null, ArchivedTweet $archivedStatus = null)
    {
        $this->archivedStatus = $archivedStatus;
        $this->status = $status;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getStatus(): TweetInterface
    {
        if ($this->status === null) {
            return $this->archivedStatus;
        }

        return $this->status;
    }
}
