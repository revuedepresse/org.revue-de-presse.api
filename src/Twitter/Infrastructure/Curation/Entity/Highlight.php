<?php

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use DateTime;

class Highlight
{
    private $id;

    /**
     * @var DateTime
     */
    private $publicationDateTime;

    /**
     * @var Tweet
     */
    private $status;

    /**
     * App\Membership\Infrastructure\Entity\Member
     */
    private $member;

    /**
     * @var boolean
     */
    private $isRetweet;

    /**
     * @var PublishersListInterface
     */
    private $aggregate;

    /**
     * @var string
     */
    private $aggregateName;

    /**
     * @var DateTime
     */
    private $retweetedStatusPublicationDate;

    /**
     * @var int
     */
    private $totalRetweets;

    /**
     * @var int
     */
    private $totalFavorites;

    public function __construct(
        MemberInterface $member,
        TweetInterface  $status,
        DateTime        $publicationDateTime
    ) {
        $this->publicationDateTime = $publicationDateTime;
        $this->member = $member;
        $this->status = $status;
    }
}
