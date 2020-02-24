<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api;

use App\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Twitter\Api\UnavailableResource;
use App\Infrastructure\Twitter\Api\UnavailableResourceHandler;
use App\Tests\Builder\MemberRepositoryBuilder;
use App\Twitter\Api\TwitterErrorAwareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @package App\Tests\Infrastructure\Twitter\Api
 * @group   unavailable_resource
 */
class UnavailableResourceHandlerTest extends TestCase
{
    /**
     * @var UnavailableResourceHandler
     */
    private UnavailableResourceHandler $handler;

    /**
     * @return array
     */
    public function getNotFoundErrorCodes(): array
    {
        return [
            [TwitterErrorAwareInterface::ERROR_NOT_FOUND],
            [TwitterErrorAwareInterface::ERROR_USER_NOT_FOUND]
        ];
    }

    /**
     * @dataProvider getNotFoundErrorCodes
     *
     * @param int $errorCode
     *
     * @test
     */
    public function it_should_handle_a_member_not_found(int $errorCode): void
    {
        // Arrange

        $this->handler = new UnavailableResourceHandler(
            MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                   ->build(),
            new NullLogger()
        );

        // Act
        try {
            $this->handler->handle(
                new MemberIdentity(
                    'mariec',
                    '1'
                ),
                UnavailableResource::ofTypeAndRootCause(
                    $errorCode,
                    'Not found'
                )
            );
        } catch (\Exception $exception) {

            // Assert

            self::assertInstanceOf(
                MembershipException::class,
                $exception
            );
            self::assertEquals(
                ExceptionalMemberInterface::NOT_FOUND_MEMBER,
                $exception->getCode()
            );

            return;
        }

        $this->fail('An exception should have been thrown');
    }

    /**
     * @throws
     *
     * @test
     */
    public function it_should_handle_a_suspended_member(): void
    {
        // Arrange

        $this->handler = new UnavailableResourceHandler(
            MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                   ->willSaveASuspendedMember()
                                   ->build(),
            new NullLogger()
        );

        // Act
        try {
            $this->handler->handle(
                new MemberIdentity(
                    'mariec',
                    '1'
                ),
                UnavailableResource::ofTypeAndRootCause(
                    TwitterErrorAwareInterface::ERROR_SUSPENDED_USER,
                    'Suspended member'
                )
            );
        } catch (\Exception $exception) {

            // Assert

            self::assertInstanceOf(
                MembershipException::class,
                $exception
            );
            self::assertEquals(
                ExceptionalMemberInterface::SUSPENDED_USER,
                $exception->getCode()
            );

            return;
        }

        $this->fail('An exception should have been thrown');
    }

    /**
     * @throws
     *
     * @test
     */
    public function it_should_handle_a_member_having_protected_tweet(): void
    {
        // Arrange

        $this->handler = new UnavailableResourceHandler(
            MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                   ->willSaveAProtectedMember()
                                   ->build(),
            new NullLogger()
        );

        // Act
        try {
            $this->handler->handle(
                new MemberIdentity(
                    'mariec',
                    '1'
                ),
                UnavailableResource::ofTypeAndRootCause(
                    TwitterErrorAwareInterface::ERROR_PROTECTED_TWEET,
                    'Protected tweet'
                )
            );
        } catch (\Exception $exception) {

            // Assert

            self::assertInstanceOf(
                MembershipException::class,
                $exception
            );
            self::assertEquals(
                ExceptionalMemberInterface::PROTECTED_ACCOUNT,
                $exception->getCode()
            );

            return;
        }

        $this->fail('An exception should have been thrown');
    }

    /**
     * @throws
     *
     * @test
     */
    public function it_should_handle_an_unavailable_resource(): void
    {
        // Arrange

        $this->handler = new UnavailableResourceHandler(
            MemberRepositoryBuilder::newMemberRepositoryBuilder()
                                   ->build(),
            new NullLogger()
        );

        // Act
        try {
            $this->handler->handle(
                new MemberIdentity(
                    'mariec',
                    '1'
                ),
                UnavailableResource::ofTypeAndRootCause(
                    TwitterErrorAwareInterface::ERROR_HOST_RESOLUTION,
                    'Host could not be resolved'
                )
            );
        } catch (\Exception $exception) {

            // Assert

            self::assertInstanceOf(
                MembershipException::class,
                $exception
            );
            self::assertEquals(
                ExceptionalMemberInterface::UNAVAILABLE_RESOURCE,
                $exception->getCode()
            );

            return;
        }

        $this->fail('An exception should have been thrown');
    }
}
