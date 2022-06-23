<?php

declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Twitter\Domain\Api\Accessor\MembersListAccessorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MembersListAccessorBuilder extends TestCase
{
    public static function willAddMembersToList(): MembersListAccessorInterface
    {
        $testCase = new self();

        /** @var MembersListAccessorInterface|ObjectProphecy $prophecy */
        $prophecy = $testCase->prophesize(MembersListAccessorInterface::class);

        $prophecy
            ->addMembersToList(
                Argument::type('array'),
                Argument::type('string')
            )
            ->willReturn(null);

        return $prophecy->reveal();
    }
}