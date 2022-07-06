<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Mutator;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Http\Mutator\FriendshipMutator;
use App\Twitter\Infrastructure\Http\Mutator\FriendshipMutatorInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
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
