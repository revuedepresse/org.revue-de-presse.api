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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophet;

class FriendshipMutatorBuilder extends TestCase
{
    use ProphecyTrait;

    public function __construct()
    {
        $this->prophet = $this->getProphet();
    }

    public function prophet(): Prophet
    {
        return $this->prophet;
    }

    public static function build(): FriendshipMutatorInterface
    {
        $testCase = new self();

        /** @var FriendshipMutatorInterface $mutator */
        $mutator = $testCase->prophet()->prophesize(FriendshipMutator::class);

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
