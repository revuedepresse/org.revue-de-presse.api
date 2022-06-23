<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
use App\Twitter\Infrastructure\Curation\Entity\FollowersListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Curation\Repository\FollowersListCollectedEventRepository;
use App\Twitter\Domain\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Api\Selector\FollowersListSelector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FollowersListCollectedEventRepositoryBuilder extends TestCase
{
    use ProphecyTrait;

    /**
     * @return ListCollectedEventRepositoryInterface
     */
    public static function build(): ListCollectedEventRepositoryInterface
    {
        $testCase = new self();
        $prophecy = $testCase->prophesize(FollowersListCollectedEventRepository::class);

        $prophecy->findBy(Argument::type('array'))
            ->will(function ($arguments) {
                $resourcePath = '../../../../../../Resources/Subscribees.b64';

                return [FollowersListCollectedEvent::jsonDeserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . '/' . $resourcePath)
                    ),
                )];
            });

        $prophecy->aggregatedLists(
            Argument::type(ListAccessorInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $followersListAccessor = FollowersListAccessorBuilder::build();

            return $followersListAccessor->getListAtCursor(
                new FollowersListSelector($arguments[1])
            );
        });

        return $prophecy->reveal();
    }
}
