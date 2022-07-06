<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FollowersBatchAwareHttpClientBuilder;
use App\Twitter\Domain\Curation\Repository\PaginatedBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Curation\Entity\FollowersListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Curation\Repository\FollowersListCollectedEventRepository;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Http\Selector\FollowersListSelector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FollowersListCollectedEventRepositoryBuilder extends TestCase
{
    use ProphecyTrait;

    public static function build(): PaginatedBatchCollectedEventRepositoryInterface
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
            Argument::type(CursorAwareHttpClientInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $FollowersBatchAwareHttpClient = FollowersBatchAwareHttpClientBuilder::build();

            return $FollowersBatchAwareHttpClient->getListAtCursor(
                new FollowersListSelector($arguments[1])
            );
        });

        return $prophecy->reveal();
    }
}
