<?php
declare (strict_types=1);

namespace App\Tests\Builder\Infrastructure\Collection\Repository;

use App\Infrastructure\Collection\Repository\MemberFriendsListCollectedEventRepository;
use App\Infrastructure\Collection\Repository\MemberFriendsListCollectedEventRepositoryInterface;
use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsAccessorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MemberFriendsListCollectedEventRepositoryBuilder extends TestCase
{
    /**
     * @return MemberFriendsListCollectedEventRepositoryInterface
     */
    public static function make(): MemberFriendsListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(MemberFriendsListCollectedEventRepository::class);
        $prophecy->aggregatedMemberFriendsLists(
            Argument::type(FriendsAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $friendsAccessor = FriendsAccessorBuilder::make();

            return $friendsAccessor->getMemberFriendsList($arguments[1]);
        });

        return $prophecy->reveal();
    }
}