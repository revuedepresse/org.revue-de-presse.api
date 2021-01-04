<?php
declare (strict_types=1);

namespace App\Tests\Membership\Builder\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\ApiAccessorBuilder;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class NetworkRepositoryBuilder extends TestCase
{
    public static function build(MemberRepositoryInterface $repository)
    {
        $testCase = new self();

        $member = self::ensureMembersExistsInDatabase($repository);

        /** @var NetworkRepositoryInterface|ObjectProphecy $prophecy */
        $prophecy = $testCase->prophesize(NetworkRepository::class);
        $prophecy->ensureMemberExists(Argument::type('string'))
            ->willReturn($member);

        return $prophecy->reveal();
    }

    /**
     * @param MemberRepositoryInterface $repository
     * @return MemberInterface
     */
    private static function ensureMembersExistsInDatabase(MemberRepositoryInterface $repository): MemberInterface
    {
        $twitterId = ApiAccessorBuilder::PUBLISHERS_LIST_MEMBER_TWITTER_ID;

        $member = MemberBuilder::build(
            ApiAccessorBuilder::PUBLISHERS_LIST_MEMBER_SCREEN_NAME,
            $twitterId
        );
        $existingMember = $repository->findOneBy(['twitterID' => $twitterId]);

        if ($existingMember instanceof MemberInterface) {
            return $existingMember;
        }

        return $repository->saveMember($member);
    }

}