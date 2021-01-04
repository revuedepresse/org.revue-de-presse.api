<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Mutator;

use App\Twitter\Infrastructure\Api\Resource\MemberCollection;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Api\Mutator\FriendshipMutator;
use App\Twitter\Infrastructure\Api\Mutator\FriendshipMutatorInterface;
use App\Membership\Domain\Model\MemberInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FriendshipMutatorBuilder extends TestCase
{
    public static function build(): FriendshipMutatorInterface
    {
        $testCase = new self();

        /** @var FriendshipMutatorInterface $mutator */
        $mutator = $testCase->prophesize(FriendshipMutator::class);

        $mutator->unfollowMembers(
            Argument::type(MemberCollection::class),
            Argument::type(MemberInterface::class)
        )->will(function ($arguments) {
            return MemberCollection::fromArray([
                new MemberIdentity(
                    'inactiveMemberName',
                    '1'
                )
            ]);
        });

        return $mutator->reveal();
    }
}