<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Twitter\Domain\Curation\Entity\FollowersListCollectedEvent;
use App\Twitter\Infrastructure\Curation\Repository\FollowersListCollectedEventRepository;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Ramsey\Uuid\Rfc4122\UuidV4;

class FollowersListCollectedEventRepositoryBuilder extends TestCase
{
    /**
     * @return ListCollectedEventRepositoryInterface
     */
    public static function make(): ListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(FollowersListCollectedEventRepository::class);

        $prophecy->findBy(Argument::type('array'))
            ->will(function ($arguments) {
                $resourcePath = '../../../../../../Resources/Subscribees.b64';

                return [FollowersListCollectedEvent::unserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . '/' . $resourcePath)
                    ),
                )];
            });

        $prophecy->aggregatedLists(
            Argument::type(ListAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $followersListAccessor = FollowersListAccessorBuilder::make();

            return $followersListAccessor->getListAtCursor(
                new FollowersListSelector(
                    UuidV4::uuid4(),
                    $arguments[1],
                )
            );
        });

        return $prophecy->reveal();
    }
}