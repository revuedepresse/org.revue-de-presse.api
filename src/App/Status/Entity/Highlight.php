<?php

namespace App\Status\Entity;

use App\Member\MemberInterface;
use Predis\Configuration\Option\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class Highlight
{
    private $id;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status
     */
    private $status;

    /**
     * @var \WTW\UserBundle\Entity\User
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
