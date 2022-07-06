<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\TweetInterface;
use Ramsey\Uuid\UuidInterface;

class NotFoundStatus
{
    /**
     * @var string
     */
    private UuidInterface $id;

    /**
     * @var Tweet
     */
    private ?Tweet $status = null;

    /**
     * @var ArchivedTweet
     */
    private ?ArchivedTweet $archivedStatus = null;

    /**
     * @param $status
     * @param $archivedStatus
     */
    public function __construct(Tweet $status = null, ArchivedTweet $archivedStatus = null)
    {
        $this->archivedStatus = $archivedStatus;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TweetInterface
     */
    public function getStatus()
    {
        if ($this->status === null) {
            return $this->archivedStatus;
        }

        return $this->status;
    }
}
