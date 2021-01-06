<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Membership\Domain\Model\MemberInterface;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\Api\Resource\MemberCollection;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use PDOException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

class ApiAccessorBuilder
{
    public const LIST_ID   = '1';
    public const LIST_NAME = 'science';

    public const MEMBER_ID          = '1';
    public const MEMBER_NAME        = 'Marie Curie';
    public const MEMBER_SCREEN_NAME = 'mariec';

    public const SCREEN_NAME = 'BobEponge';
    public const PUBLISHERS_LIST_MEMBER_SCREEN_NAME = 'publishers-list-member';
    public const PUBLISHERS_LIST_MEMBER_TWITTER_ID = '2';

    public static function newApiAccessorBuilder()
    {
        return new self();
    }

    private ObjectProphecy $prophecy;

    public function __construct()
    {
        $prophet = new Prophet();

        $this->prophecy = $prophet->prophesize(ApiAccessorInterface::class);
    }

    public function build(): ApiAccessorInterface
    {
        return $this->prophecy->reveal();
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

    public function makeOwnershipCollection(): OwnershipCollectionInterface
    {
        return OwnershipCollection::fromArray(
            [
                self::LIST_NAME => (object) [
                    'name'   => self::LIST_NAME,
                    'id'     => (int) self::LIST_ID,
                    'id_str' => self::LIST_ID,
                ]
            ],
            0
        );
    }

    public function willGetMembersInList(
        string $listId,
        MemberCollection $members
    ): self {
        $this->prophecy
            ->getListMembers($listId)
            ->willReturn($members);

        return $this;
    }

    public function willGetOwnershipCollectionForMember(
        OwnershipCollectionInterface $ownershipCollection,
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships(Argument::type(ListSelectorInterface::class))
            ->will(function ($arguments) use ($ownershipCollection) {
                if ($arguments[0] instanceof ListSelectorInterface &&
                    $arguments[0]->cursor() !== '0'
                ) {
                    return $ownershipCollection;
                }

                return OwnershipCollection::fromArray([]);
            });

        return $this;
    }

    public function willThrowWhenGettingOwnershipCollectionForMember(
        string $screenName
    ): self {
        $this->prophecy
            ->getMemberOwnerships(Argument::type(ListSelectorInterface::class))
            ->willThrow(new UnavailableResourceException());

        return $this;
    }

    public function willGetOwnershipCollectionAfterThrowingForMember(
        OwnershipCollectionInterface $ownershipCollection,
        string $screenName
    ): self {
        static $calls = 0;

        $this->prophecy
            ->getMemberOwnerships(Argument::type(ListSelectorInterface::class))
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

    public function willEnsureMemberHavingNameExists(
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger,
        string $screenName
    ): self {
        $member = MemberBuilder::build($screenName);

        try {
            $existingMember = $memberRepository->findOneBy(['twitterID' => $member->twitterId()]);
            if ($existingMember instanceof MemberInterface) {
                $member = $existingMember;
            } else {
                $member = $memberRepository->saveMember($member);
            }
        } catch (Throwable $e) {
            if ($e instanceof PDOException) {
                $logger->error($e->getMessage(), ['exception' => $e]);
            }

            return $this;
        }

        $this->prophecy
            ->ensureMemberHavingNameExists($screenName)
            ->willReturn($member);

        return $this;
    }

    public static function willAllowPublishersListToBeImportedForMemberHavingScreenName(
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger,
        string $screenName
    )
    {
        $builder = new self();

        $builder->willGetOwnershipCollectionForMember(
            $builder->makeOwnershipCollection(),
            $screenName
        );

        $builder->willEnsureMemberHavingNameExists($memberRepository, $logger, $screenName);
        $builder->willGetMembersInList(
            self::LIST_ID,
            MemberCollection::fromArray([
                new MemberIdentity(
                    self::PUBLISHERS_LIST_MEMBER_SCREEN_NAME,
                    self::PUBLISHERS_LIST_MEMBER_TWITTER_ID
                )
            ])
        );

        return $builder->build();
    }
}