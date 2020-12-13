<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Twitter\Domain\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Infrastructure\Curation\Repository\FriendsListCollectedEventRepository;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FriendsListSelector;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FriendsListAccessorBuilder;
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
                $resourcePath = '../../../../../../Resources/Subscriptions.b64';

                return [FriendsListCollectedEvent::unserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . '/' . $resourcePath)
                    ),
                )];
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