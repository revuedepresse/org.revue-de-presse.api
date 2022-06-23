<?php

declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Builder\Accessor;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Twitter\Domain\Api\Accessor\StatusAccessorInterface;
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