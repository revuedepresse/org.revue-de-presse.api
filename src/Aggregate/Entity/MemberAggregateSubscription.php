<?php

namespace App\Aggregate\Entity;

use App\Membership\Entity\MemberInterface;

class MemberAggregateSubscription
{
    /**
     * @var string
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @var string
     */
    public $listId;

    /**
     * @var string
     */
    private $listName;

    /**
     * @var string
     */
    private $document;

    /**
     * @param MemberInterface $member
     * @param array           $document
     */
    public function __construct(MemberInterface $member, array $document)
    {
        $this->member = $member;
        $this->document = json_encode($document);
        $this->listName = $document['name'];
        $this->listId = $document['id'];
    }
}
