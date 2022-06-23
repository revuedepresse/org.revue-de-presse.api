<?php

declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Twitter\Domain\Api\Accessor\StatusAccessorInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class StatusAccessorBuilder extends TestCase
{
    public static function willEnsureMemberHavingNameExists(
        MemberRepositoryInterface $memberRepository
    ): StatusAccessorInterface
    {
        $testCase = new self();

        $prophecy = $testCase->prophesize(StatusAccessorInterface::class);

        $prophecy
            ->ensureMemberHavingNameExists(Argument::type('string'))
            ->will(function ($arguments) use ($memberRepository) {
                $member = MemberBuilder::build($arguments[0]);
                $memberRepository->saveMember($member);

                return $member;
            });

        return $prophecy->reveal();
    }
}