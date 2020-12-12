<?php
declare (strict_types=1);

namespace App\Tests\Builder\Twitter\Api\Mutator;

use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Twitter\Api\Mutator\FriendshipMutator;
use App\Infrastructure\Twitter\Api\Mutator\FriendshipMutatorInterface;
use App\Membership\Entity\MemberInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FriendshipMutatorBuilder extends TestCase
{
    public static function make(): FriendshipMutatorInterface
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