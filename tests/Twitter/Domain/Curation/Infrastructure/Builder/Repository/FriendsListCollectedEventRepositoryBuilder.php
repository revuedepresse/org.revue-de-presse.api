<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Twitter\Infrastructure\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Infrastructure\Curation\Repository\FriendsListCollectedEventRepository;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Domain\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Api\Selector\FriendsListSelector;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FriendsListAccessorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;

class FriendsListCollectedEventRepositoryBuilder extends TestCase
{
    use ProphecyTrait;

    /**
     * @return ListCollectedEventRepositoryInterface
     */
    public static function build(): ListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(FriendsListCollectedEventRepository::class);

        $prophecy->findBy(Argument::type('array'))
            ->will(function ($arguments) {
                $resourcePath = '../../../../../../Resources/Subscriptions.b64';

                return [FriendsListCollectedEvent::jsonDeserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . '/' . $resourcePath)
                    ),
                )];
            });

        $prophecy->aggregatedLists(
            Argument::type(ListAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $friendsListAccessor = FriendsListAccessorBuilder::build();

            return $friendsListAccessor->getListAtCursor(
                new FriendsListSelector($arguments[1])
            );
        });

        return $prophecy->reveal();
    }
}
