<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Twitter\Domain\Api\MemberOwnershipsAccessorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Domain\Resource\OwnershipCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;

class MemberOwnershipsAccessorBuilder extends TestCase
{
    public static function build()
    {
        $testCase = new self();

        /** @var MemberOwnershipsAccessorInterface|ProphecyInterface $accessor */
        $accessor = $testCase->prophesize(MemberOwnershipsAccessorInterface::class);
        $accessor->getMemberOwnerships(
            Argument::type(ListSelectorInterface::class)
        )
        ->willReturn(OwnershipCollection::fromArray([]));

        return $accessor->reveal();
    }
}