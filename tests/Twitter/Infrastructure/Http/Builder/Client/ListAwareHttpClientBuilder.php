<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ListAwareHttpClientBuilder extends TestCase
{
    public const LIST_ID   = '1';
    public const LIST_NAME = 'science';

    public static function build()
    {
        /** @var ListAwareHttpClientInterface|ObjectProphecy $accessor */
        $accessor = self::prophesizeAccessor();
        $accessor->getMemberOwnerships(
            Argument::type(ListSelectorInterface::class)
        )
        ->willReturn(OwnershipCollection::fromArray([]));

        return $accessor->reveal();
    }

    public static function willGetOwnershipCollectionForMember(
        OwnershipCollectionInterface $ownershipCollection
    ): ListAwareHttpClientInterface {
        /** @var ListAwareHttpClientInterface|ObjectProphecy $accessor */
        $accessor = self::prophesizeAccessor();
        $accessor
            ->getMemberOwnerships(Argument::type(ListSelectorInterface::class))
            ->will(function ($arguments) use ($ownershipCollection) {
                if ($arguments[0] instanceof ListSelectorInterface &&
                    $arguments[0]->cursor() !== '0'
                ) {
                    return $ownershipCollection;
                }

                return OwnershipCollection::fromArray([]);
            });

        return $accessor->reveal();
    }

    public static function makeOwnershipCollection(): OwnershipCollectionInterface
    {
        return OwnershipCollection::fromArray(
            [
                self::LIST_NAME => (object) [
                    'name'   => self::LIST_NAME,
                    'id'     => (int) self::LIST_ID,
                    'id_str' => self::LIST_ID,
                ]
            ],
            0
        );
    }

    public static function willAllowPublishersListToBeImportedForMemberHavingScreenName()
    {
        return self::willGetOwnershipCollectionForMember(
            self::makeOwnershipCollection(),
        );
    }

    public static function willReturnSomeOwnership()
    {
        /** @var ListAwareHttpClientInterface|ObjectProphecy $accessor */
        $accessor = self::prophesizeAccessor();
        $accessor->getMemberOwnerships(
            Argument::type(ListSelectorInterface::class)
        )
        ->willReturn(OwnershipCollection::fromArray([
            (object) [
                'id_str' => '3',
                'name' => 'philosophy'
            ],
            (object) [
                'id_str' => '1',
                'name' => 'science'
            ],
            (object) [
                'id_str' => '2',
                'name' => 'technology'
            ],
        ]));

        return $accessor->reveal();
    }

    private static function prophesizeAccessor()
    {
        $testCase = new self();

        return $testCase->prophesize(ListAwareHttpClientInterface::class);
    }
}