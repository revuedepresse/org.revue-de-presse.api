<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Tests\Twitter\Infrastructure\PublishersList\Console\ImportMemberPublishersListsCommandTest;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use PDOException;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

class HttpClientBuilder
{
    use ProphecyTrait;

    public const LIST_ID   = '1';
    public const LIST_NAME = 'science';

    public const MEMBER_ID          = '1';
    public const MEMBER_NAME        = 'Marie Curie';
    public const MEMBER_SCREEN_NAME = 'mariec';

    public const PUBLISHERS_LIST_MEMBER_SCREEN_NAME = 'publishers-list-member';
    public const PUBLISHERS_LIST_MEMBER_TWITTER_ID  = '2';

    public const SCREEN_NAME                        = 'BobEponge';

    private ObjectProphecy $prophecy;

    public function __construct()
    {
        $prophet = $this->getProphet();

        $this->prophecy = $prophet->prophesize(HttpClientInterface::class);
        $this->prophecy->getApiBaseUrl()->willReturn('https://example.com');
        $this->prophecy->guardAgainstApiLimit(Argument::type('string'))->willReturn(null);
    }

    public function build(): HttpClientInterface
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

    public function willGetOwnershipCollectionForMember(OwnershipCollectionInterface $ownershipCollection): self {
        $this->prophecy
            ->contactEndpoint(Argument::type('string'))
            ->will(function ($arguments) use ($ownershipCollection) {
                if ($arguments[0] instanceof ListSelectorInterface &&
                    $arguments[0]->cursor() !== '0'
                ) {
                    return (object) [
                        'lists' => $ownershipCollection->toArray(),
                        'next_cursor' => $ownershipCollection->nextPage()
                    ];
                }

                return (object) [
                    'lists' => $ownershipCollection->toArray(),
                    'next_cursor' => $ownershipCollection->nextPage()
                ];
            });

        return $this;
    }

    public function willThrowWhenGettingOwnershipCollectionForMember(): self {
        $this->prophecy
            ->contactEndpoint(Argument::type('string'))
            ->willThrow(new UnavailableResourceException());

        return $this;
    }

    public function willGetOwnershipCollectionAfterThrowingForMember(
        OwnershipCollectionInterface $ownershipCollection
    ): self {
        static $calls = 0;

        $this->prophecy
            ->contactEndpoint(Argument::type('string'))
            ->will(function () use (&$calls, $ownershipCollection) {
                if ($calls === 0) {
                    $calls++;

                    throw new UnavailableResourceException();
                }

                return (object) [
                    'lists' => array_map(
                        static function (PublishersList $list) {
                            return (object) [
                                'name' => $list->name(),
                                'id' => (int) $list->id(),
                                'id_str' => $list->id(),
                            ];
                        },
                        $ownershipCollection->toArray()
                    ),
                    'next_cursor' => $ownershipCollection->nextPage()
                ];
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
        LoggerInterface $logger
    )
    {
        $builder = new self();

        $builder->willGetOwnershipCollectionForMember(
            $builder->makeOwnershipCollection(),
        );

        $builder->willEnsureMemberHavingNameExists($memberRepository, $logger, ImportMemberPublishersListsCommandTest::SCREEN_NAME);
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
