<?php

namespace App\Status\Entity;

use App\Member\MemberInterface;
use Predis\Configuration\Option\Aggregate;
use App\Api\Entity\StatusInterface;

class Highlight
{
    private $id;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;

    /**
     * @var \App\Api\Entity\Status
     */
    private $status;

    /**
     * @var \App\Member\Entity\Member
     */
    private $member;

    /**
     * @var boolean
     */
    private $isRetweet;

    /**
     * @var Aggregate
     */
    private $aggregate;

    /**
     * @var string
     */
    private $aggregateName;

    /**
     * @var \DateTime
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
        \DateTime $publicationDateTime
    ) {
        $this->publicationDateTime = $publicationDateTime;
        $this->member = $member;
        $this->status = $status;
    }
}
