<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FriendsBatchAwareHttpClientBuilder;
use App\Twitter\Domain\Curation\Repository\PaginatedBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Infrastructure\Curation\Repository\FriendsListCollectedEventRepository;
use App\Twitter\Infrastructure\Http\Selector\FriendsListSelector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;

class FriendsListCollectedEventRepositoryBuilder extends TestCase
{
    use ProphecyTrait;

    public static function build(): PaginatedBatchCollectedEventRepositoryInterface
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
            Argument::type(CursorAwareHttpClientInterface::class),
            Argument::type('string')
        )->will(function ($arguments) {
            $friendsListAccessor = FriendsBatchAwareHttpClientBuilder::build();

            return $friendsListAccessor->getListAtCursor(
                new FriendsListSelector($arguments[1])
            );
        });

        return $prophecy->reveal();
    }
}
