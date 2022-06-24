<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http;

use App\Membership\Domain\Exception\ExceptionalMemberInterface;
use App\Membership\Domain\Exception\MembershipException;
use App\Tests\Membership\Builder\Repository\MemberRepositoryBuilder;
use App\Twitter\Domain\Http\ApiErrorCodeAwareInterface;
use App\Twitter\Infrastructure\Http\UnavailableResource;
use App\Twitter\Infrastructure\Http\UnavailableResourceHandler;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
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
            [ApiErrorCodeAwareInterface::ERROR_NOT_FOUND],
            [ApiErrorCodeAwareInterface::ERROR_USER_NOT_FOUND]
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

        self::fail('An exception should have been thrown');
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
                    ApiErrorCodeAwareInterface::ERROR_SUSPENDED_USER,
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

        self::fail('An exception should have been thrown');
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
                    ApiErrorCodeAwareInterface::ERROR_PROTECTED_TWEET,
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

        self::fail('An exception should have been thrown');
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
                    ApiErrorCodeAwareInterface::ERROR_HOST_RESOLUTION,
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

        self::fail('An exception should have been thrown');
    }
}
