<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Domain\Api\Accessor\MemberProfileAccessorInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MemberProfileAccessorBuilder extends TestCase
{
    public static function build(): MemberProfileAccessorInterface
    {
        $testCase = new self();

        /** @var MemberProfileAccessorInterface|ObjectProphecy $accessor */
        $accessor = $testCase->prophesize(MemberProfileAccessorInterface::class);

        $accessor->getMemberByIdentity(Argument::type(MemberIdentity::class))
            ->will(function ($arguments) {
                /** @var \App\Twitter\Infrastructure\Http\Resource\MemberIdentity $memberIdentity */
                $memberIdentity = $arguments[0];

                return (new Member())
                    ->setTwitterID($memberIdentity->id())
                    ->setTwitterScreenName($memberIdentity->screenName())
                    ->setEmail('@'.$memberIdentity->screenName());
            });

        return $accessor->reveal();
    }
}