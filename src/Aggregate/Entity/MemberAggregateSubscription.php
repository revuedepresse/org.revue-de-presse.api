<?php
declare(strict_types=1);

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
    private MemberInterface $member;

    /**
     * @var string
     */
    public string $listId;

    /**
     * @var string
     */
    private string $listName;

    /**
     * @var string
     */
    private string $document;

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
