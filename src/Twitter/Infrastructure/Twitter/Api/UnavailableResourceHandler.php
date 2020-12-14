<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api;

use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use Psr\Log\LoggerInterface;
use function sprintf;

class UnavailableResourceHandler implements UnavailableResourceHandlerInterface
{
    private const EXCEPTION_MEMBER_NOT_FOUND     = 'User with screen name %s can not be found';
    private const EXCEPTION_SUSPENDED_MEMBER     = 'User with screen name "%s" has been suspended (code: %d, message: "%s")';
    private const EXCEPTION_PROTECTED_MEMBER     = 'User with screen name "%s" has a protected account (code: %d, message: "%s")';
    private const EXCEPTION_UNAVAILABLE_RESOURCE = 'Unavailable resource for user with screen name %s (code: %d, message: "%s")';

    /**
     * @var MemberRepositoryInterface
     */
    private MemberRepositoryInterface $memberRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger
    ) {
        $this->memberRepository = $memberRepository;
        $this->logger = $logger;
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @throws MembershipException
     */
    public function handle(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void {
        $this->handleMemberNotFound($memberIdentity, $resource);
        $this->handleSuspendedMember($memberIdentity, $resource);
        $this->handleProtectedMember($memberIdentity, $resource);
        $this->handleUnavailableResource($memberIdentity, $resource);
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @throws MembershipException
     */
    private function handleMemberNotFound(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void {
        if ($resource->isMemberNotFound() || $resource->isResourceNotFound()) {
            $message = sprintf(
                self::EXCEPTION_MEMBER_NOT_FOUND,
                $memberIdentity->screenName()
            );
            $this->logger->info($message);

            throw new MembershipException($message, self::NOT_FOUND_MEMBER);
        }
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @return void
     * @throws MembershipException
     */
    private function handleProtectedMember(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void {
        if ($resource->isMemberProtected()) {
            $message = sprintf(
                self::EXCEPTION_PROTECTED_MEMBER,
                $memberIdentity->screenName(),
                $resource->getType(),
                $resource->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveProtectedMember(
                $memberIdentity
            );

            MembershipException::throws($message, self::PROTECTED_ACCOUNT);
        }
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @return void
     * @throws MembershipException
     */
    private function handleSuspendedMember(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void {
        if ($resource->isMemberSuspended()) {
            $message = sprintf(
                self::EXCEPTION_SUSPENDED_MEMBER,
                $memberIdentity->screenName(),
                $resource->getType(),
                $resource->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveSuspendedMember(
                $memberIdentity
            );

            MembershipException::throws($message, self::SUSPENDED_USER);
        }
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @throws MembershipException
     */
    private function handleUnavailableResource(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void {
        $message = sprintf(
            self::EXCEPTION_UNAVAILABLE_RESOURCE,
            $memberIdentity->screenName(),
            $resource->getType(),
            $resource->getMessage()
        );
        $this->logger->error($message);

        MembershipException::throws($message, self::UNAVAILABLE_RESOURCE);
    }
}