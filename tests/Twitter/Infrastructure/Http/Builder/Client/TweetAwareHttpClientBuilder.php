<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Tests\Membership\Builder\Entity\Legacy\MemberBuilder;
use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class TweetAwareHttpClientBuilder extends TestCase
{
    public static function willEnsureMemberHavingNameExists(
        MemberRepositoryInterface $memberRepository
    ): TweetAwareHttpClientInterface
    {
        $testCase = new self();

        $prophecy = $testCase->prophesize(TweetAwareHttpClientInterface::class);

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