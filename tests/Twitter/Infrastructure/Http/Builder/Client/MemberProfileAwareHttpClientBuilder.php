<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class MemberProfileAwareHttpClientBuilder extends TestCase
{
    public static function build(): MemberProfileAwareHttpClientInterface
    {
        $testCase = new self();

        /** @var MemberProfileAwareHttpClientInterface|ObjectProphecy $accessor */
        $accessor = $testCase->prophesize(MemberProfileAwareHttpClientInterface::class);

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