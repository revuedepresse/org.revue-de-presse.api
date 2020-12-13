<?php

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTime;

class Highlight
{
    private $id;

    /**
     * @var DateTime
     */
    private $publicationDateTime;

    /**
     * @var Status
     */
    private $status;

    /**
     * App\Membership\Domain\Entity\Member
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
        StatusInterface $status,
        DateTime $publicationDateTime
    ) {
        $this->publicationDateTime = $publicationDateTime;
        $this->member = $member;
        $this->status = $status;
    }
}
