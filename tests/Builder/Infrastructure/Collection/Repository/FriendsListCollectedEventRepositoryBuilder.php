<?php
declare (strict_types=1);

namespace App\Tests\Builder\Infrastructure\Collection\Repository;

use App\Infrastructure\Collection\Repository\FriendsListCollectedEventRepository;
use App\Infrastructure\Collection\Repository\ListCollectedEventRepositoryInterface;
use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsListAccessorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FriendsListCollectedEventRepositoryBuilder extends TestCase
{
    /**
     * @return ListCollectedEventRepositoryInterface
     */
    public static function make(): ListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(FriendsListCollectedEventRepository::class);
        $prophecy->aggregatedLists(
            Argument::type(ListAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $FriendsListAccessor = FriendsListAccessorBuilder::make();

            return $FriendsListAccessor->getListAtDefaultCursor($arguments[1]);
        });

        return $prophecy->reveal();
    }
}