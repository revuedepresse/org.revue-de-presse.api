<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Twitter\Domain\Http\Client\MembersBatchAwareHttpClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MembersBatchAwareHttpClientBuilder extends TestCase
{
    public static function willAddMembersToList(): MembersBatchAwareHttpClientInterface
    {
        $testCase = new self();

        /** @var MembersBatchAwareHttpClientInterface|ObjectProphecy $prophecy */
        $prophecy = $testCase->prophesize(MembersBatchAwareHttpClientInterface::class);

        $prophecy
            ->addMembersToList(
                Argument::type('array'),
                Argument::type('string')
            )
            ->willReturn(null);

        return $prophecy->reveal();
    }
}