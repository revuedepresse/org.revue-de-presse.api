<?php

namespace App\Membership\Infrastructure\Entity;

use App\Subscription\Infrastructure\Entity\ListSubscription;
use App\Membership\Domain\Model\MemberInterface;
use Ramsey\Uuid\UuidInterface;

class MemberInList
{
    private UuidInterface $id;

    private ListSubscription $list;

    public function getList(): ListSubscription {
        return $this->list;
    }

    public MemberInterface $memberInList;

    public function __construct(
        ListSubscription $list,
        MemberInterface  $memberInList
    ) {
        $this->list = $list;
        $this->memberInList = $memberInList;
    }
}
