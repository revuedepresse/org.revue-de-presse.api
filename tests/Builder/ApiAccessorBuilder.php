<?php
declare(strict_types=1);

namespace App\Tests\Builder;

use App\Twitter\Api\Accessor;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Api\Resource\OwnershipCollection;
use App\Twitter\Exception\UnavailableResourceException;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use stdClass;

class ApiAccessorBuilder
{
    public const LIST_ID   = 1;
    public const LIST_NAME = 'science';

    public const MEMBER_ID          = '1';
    public const MEMBER_NAME        = 'Marie Curie';
    public const MEMBER_SCREEN_NAME = 'mariec';

    public const SCREEN_NAME = 'BobEponge';

    public static function newApiAccessorBuilder()
    {
        return new self();
    }

    private ObjectProphecy $prophecy;

    private ?ApiAccessorInterface $reference = null;

    public function __construct()
    {
        $prophet = new Prophet();

        $this->prophecy = $prophet->prophesize(ApiAccessorInterface::class);
    }

    public function build(): ApiAccessorInterface
    {
        $this->reference = $this->prophecy->reveal();

        return $this->reference;
    }

    /**
     * @return object
     */
    public function makeMemberList(): stdClass
    {
        return (object) [
            'users' => [
                (object) [
                    'name'        => self::MEMBER_NAME,
                    'id'          => self::MEMBER_ID,
                    'screen_name' => self::MEMBER_SCREEN_NAME
                ]
            ]
        ];
    }

    /**
     * @return OwnershipCollection
     */
    public function makeOwnershipCollection(): OwnershipCollection
    {
        return OwnershipCollection::fromArray(
            [
                self::LIST_NAME => (object) [
                    'name'   => self::LIST_NAME,
                    'id'     => self::LIST_ID,
                    'id_str' => (string) self::LIST_ID,
                ]
            ]
        );
    }

    public function willGetMembersInList(
        int $listId,
        \stdClass $members
    ): self {
        $this->prophecy
            ->getListMembers($listId)
            ->willReturn($members);

        return $this;
    }

    public function willGetOwnershipCollectionForMember(
        OwnershipCollection $ownershipCollection,
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->willReturn($ownershipCollection);

        return $this;
    }

    public function willThrowWhenGettingOwnershipCollectionForMember(
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->willThrow(new UnavailableResourceException());

        return $this;
    }

    public function willGetOwnershipCollectionAfterThrowingForMember(
        OwnershipCollection $ownershipCollection,
        string $screenName
    ): self {
        static $calls = 0;

        $this->prophecy
            ->getMemberOwnerships($screenName)
            ->will(function () use (&$calls, $ownershipCollection) {
                if ($calls === 0) {
                    $calls++;

                    throw new UnavailableResourceException();
                }

                return $ownershipCollection;
            });

        return $this;
    }

    public function willGetProfileForMemberHavingScreenName(
        stdClass $profile,
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberProfile($screenName)
            ->willReturn($profile);

        return $this;
    }

    public function willReceiveConsumerKey(string $consumerKey): self
    {
        $this->prophecy
            ->setConsumerKey($consumerKey)
            ->willReturn(new Accessor());

        return $this;
    }

    public function willReceiveConsumerSecret(string $consumerSecret): self
    {
        $this->prophecy
            ->setConsumerSecret($consumerSecret)
            ->willReturn(new Accessor());

        return $this;
    }

    public function willReceiveSecret(string $secret): self
    {
        $this->prophecy
            ->setOAuthSecret($secret)
            ->willReturn(new Accessor());

        return $this;
    }

    public function willReceiveToken(string $token): self
    {
        $this->prophecy
            ->setOAuthToken($token)
            ->willReturn(new Accessor());

        return $this;
    }
}