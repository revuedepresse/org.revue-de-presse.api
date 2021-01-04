<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Twitter\Domain\Api\MemberOwnershipsAccessorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MemberOwnershipsAccessorBuilder extends TestCase
{
    public static function build()
    {
        /** @var MemberOwnershipsAccessorInterface|ObjectProphecy $accessor */
        $accessor = self::prophesizeAccessor();
        $accessor->getMemberOwnerships(
            Argument::type(ListSelectorInterface::class)
        )
        ->willReturn(OwnershipCollection::fromArray([]));

        return $accessor->reveal();
    }

    public static function willReturnSomeOwnership()
    {
        /** @var MemberOwnershipsAccessorInterface|ObjectProphecy $accessor */
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

        return $testCase->prophesize(MemberOwnershipsAccessorInterface::class);
    }
}