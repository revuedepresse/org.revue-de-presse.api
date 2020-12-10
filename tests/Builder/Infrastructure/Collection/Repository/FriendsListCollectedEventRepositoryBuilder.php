<?php
declare (strict_types=1);

namespace App\Tests\Builder\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\FriendsListCollectedEvent;
use App\Infrastructure\Collection\Repository\FriendsListCollectedEventRepository;
use App\Infrastructure\Collection\Repository\ListCollectedEventRepositoryInterface;
use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Infrastructure\Twitter\Api\Selector\FriendsListSelector;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsListAccessorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Ramsey\Uuid\Rfc4122\UuidV4;

class FriendsListCollectedEventRepositoryBuilder extends TestCase
{
    /**
     * @return ListCollectedEventRepositoryInterface
     */
    public static function make(): ListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(FriendsListCollectedEventRepository::class);

        $prophecy->findBy(Argument::type('array'))
                ->will(function ($arguments) {
                    $resourcePath = '../../../../Resources/Subscriptions.b64';

                    return unserialize(
                        base64_decode(
                            file_get_contents(__DIR__.'/'.$resourcePath)
                        )
                    );
                });

        $prophecy->aggregatedLists(
            Argument::type(ListAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $friendsListAccessor = FriendsListAccessorBuilder::make();

            return $friendsListAccessor->getListAtCursor(
                new FriendsListSelector(
                    UuidV4::uuid4(),
                    $arguments[1]
                )
            );
        });

        return $prophecy->reveal();
    }
}