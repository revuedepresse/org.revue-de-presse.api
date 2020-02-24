<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api\Accessor;

use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Twitter\Api\Accessor\MemberProfileAccessor;
use App\Infrastructure\Twitter\Api\UnavailableResourceHandler;
use App\Infrastructure\Twitter\Api\UnavailableResourceHandlerInterface;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Tests\Builder\MemberRepositoryBuilder;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Api\TwitterErrorAwareInterface;
use App\Twitter\Exception\UnavailableResourceException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @package App\Tests\Infrastructure\Twitter\Api\Accessor
 * @group membership
 */
class MemberProfileAccessorTest extends TestCase
{
    /**
     * @test
     *
     * @throws
     */
    public function it_returns_a_pre_existing_member(): void
    {
        // Arrange

        $expectedMember = (new Member())
            ->setTwitterID('1')
            ->setScreenName('mariec');

        $memberRepositoryBuilder = MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                                          ->willFindAMemberByTwitterId(
                                                              '1',
                                                              $expectedMember
                                                          );
        $memberRepository        = $memberRepositoryBuilder->build();

        $memberProfileAccessor = new MemberProfileAccessor(
            $this->prophesizeApiAccessor((object) ['screen_name' => 'mariec']),
            $memberRepository,
            $this->prophesizeUnavailableResourceHandler()
        );

        // Act

        $member = $memberProfileAccessor->getMemberByIdentity(
            new MemberIdentity(
                'mariec',
                '1'
            )
        );

        // Assert

        self::assertEquals($expectedMember, $member);
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_can_not_fetch_a_member_profile(): void
    {
        // Arrange

        $memberRepositoryBuilder = MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                                          ->willFindAMemberByTwitterId(
                                                              '1',
                                                              null
                                                          );
        $memberRepository        = $memberRepositoryBuilder->build();

        $memberProfileAccessor = new MemberProfileAccessor(
            $this->prophesizeApiAccessor(null),
            $memberRepository,
            $this->prophesizeUnavailableResourceHandler()
        );

        // Act

        try {
            $member = $memberProfileAccessor->getMemberByIdentity(
                new MemberIdentity(
                    'non_existing_member',
                    '1'
                )
            );
        } catch (Exception $exception) {
            // Assert

            self::assertInstanceOf(UnexpectedApiResponseException::class, $exception);
        }
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_can_fetch_a_member_profile(): void
    {
        // Arrange

        $memberRepositoryBuilder = MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                                          ->willFindAMemberByTwitterId(
                                                              '1',
                                                              null
                                                          )->willSaveMemberFromIdentity();
        $memberRepository        = $memberRepositoryBuilder->build();

        $memberProfileAccessor = new MemberProfileAccessor(
            $this->prophesizeApiAccessor((object) ['screen_name' => 'existing_member']),
            $memberRepository,
            $this->prophesizeUnavailableResourceHandler()
        );

        // Act

        $expectedTwitterUserName = 'existing_member';

        $member = $memberProfileAccessor->getMemberByIdentity(
            new MemberIdentity(
                $expectedTwitterUserName,
                '1'
            )
        );

        self::assertInstanceOf(MemberInterface::class, $member);
        self::assertTrue($member->hasNotBeenDeclaredAsNotFound());
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_can_fetch_a_member_profile_which_could_not_be_found(): void
    {
        // Arrange

        $expectedMember = (new Member())
            ->setTwitterID('1')
            ->setScreenName('mariec')
            ->setNotFound(true);

        $memberRepositoryBuilder = MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                                          ->willFindAMemberByTwitterId(
                                                              '1',
                                                              $expectedMember
                                                          )
                                                          ->willDeclareAMemberAsFound($expectedMember);

        $memberRepository        = $memberRepositoryBuilder->build();

        $memberProfileAccessor = new MemberProfileAccessor(
            $this->prophesizeApiAccessor((object) ['screen_name' => 'existing_member']),
            $memberRepository,
            $this->prophesizeUnavailableResourceHandler()
        );

        // Act

        $expectedTwitterUserName = 'existing_member';

        $member = $memberProfileAccessor->getMemberByIdentity(
            new MemberIdentity(
                $expectedTwitterUserName,
                '1'
            )
        );

        self::assertInstanceOf(MemberInterface::class, $member);
        self::assertEquals($expectedTwitterUserName, $member->getTwitterUsername());
    }

    /**
     * @param \stdClass|null $memberProfile
     *
     * @return ApiAccessorInterface
     */
    private function prophesizeApiAccessor(\stdClass $memberProfile = null): ApiAccessorInterface
    {
        $accessor = $this->prophesize(ApiAccessorInterface::class);

        $accessor->getMemberProfile('mariec')
                 ->willReturn($memberProfile);

        $accessor->getMemberProfile('non_existing_member')
                 ->willThrow(new UnavailableResourceException(
                    'Host could not be resolved',
                        TwitterErrorAwareInterface::ERROR_HOST_RESOLUTION
                 ));

         $accessor->getMemberProfile('existing_member')
                 ->willReturn($memberProfile);

        return $accessor->reveal();
    }

    /**
     * @return UnavailableResourceHandlerInterface
     */
    private function prophesizeUnavailableResourceHandler(): UnavailableResourceHandlerInterface
    {
        return $this->prophesize(UnavailableResourceHandler::class)
                    ->reveal();
    }
}
