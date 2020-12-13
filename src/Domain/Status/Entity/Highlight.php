<?php

namespace App\Domain\Status\Entity;

use App\Api\Entity\Status;
use App\Domain\Publication\PublicationListInterface;
use App\Membership\Entity\MemberInterface;
use App\Domain\Status\StatusInterface;
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
     * App\Membership\Entity\Member
     */
    private $member;

    /**
     * @var boolean
     */
    private $isRetweet;

    /**
     * @var PublicationListInterface
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
