<?php

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Domain\Publication\MembersListInterface;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTime;

class Highlight
{
    private $id;

    private \DateTimeInterface $publicationDateTime;

    private StatusInterface $status;

    private MemberInterface $member;

    private bool $isRetweet;

    private MembersListInterface $list;

    private string $aggregateName;

    private \DateTimeInterface $retweetedStatusPublicationDate;

    private int $totalRetweets;

    private int $totalFavorites;

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
