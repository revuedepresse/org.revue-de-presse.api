<?php
declare(strict_types=1);

namespace App\Conversation\Producer;

use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Domain\Membership\Exception\MembershipException;
use App\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Membership\Model\Member;
use App\Domain\Resource\MemberIdentity;
use App\Twitter\Exception\UnavailableResourceException;
use Exception;

trait MemberAwareTrait
{
    use MemberRepositoryTrait;

    /**
     * @param MemberIdentity               $memberIdentity
     * @param UnavailableResourceException $exception
     *
     * @throws MembershipException
     */
    protected function handleUnavailableResourceException(
        MemberIdentity $memberIdentity,
        UnavailableResourceException $exception
    ): void {
        if (
            $exception->getCode() === $this->accessor->getMemberNotFoundErrorCode()
            || $exception->getCode() === $this->accessor->getUserNotFoundErrorCode()
        ) {
            $message = sprintf('User with screen name %s can not be found', $memberIdentity->screenName());
            $this->logger->info($message);

            throw new MembershipException($message, self::NOT_FOUND_MEMBER);
        }

        if ($exception->getCode() === $this->accessor->getSuspendedUserErrorCode()) {
            $message = sprintf(
                'User with screen name "%s" has been suspended (code: %d, message: "%s")',
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveSuspended(
                $memberIdentity,
                (object) ['screen_name' => $memberIdentity->screenName()],
            );

            MembershipException::throws($message, self::SUSPENDED_USER);
        }

        if ($exception->getCode() === $this->accessor->getProtectedAccountErrorCode()) {
            $message = sprintf(
                'User with screen name "%s" has a protected account (code: %d, message: "%s")',
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveProtectedMember($memberIdentity);

            MembershipException::throws($message, self::PROTECTED_ACCOUNT);
        }

        $message = sprintf(
            'Unavailable resource for user with screen name %s (code: %d, message: "%s")',
            $memberIdentity->screenName(),
            $exception->getCode(),
            $exception->getMessage()
        );
        $this->logger->error($message);

        MembershipException::throws($message, self::UNAVAILABLE_RESOURCE);
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return Member
     * @throws Exception
     */
    private function getMessageMember(MemberIdentity $memberIdentity)
    {
        /** @var Member $member */
        $member            = $this->memberRepository->findOneBy(
            ['twitterID' => $memberIdentity->id()]
        );
        $preExistingMember = $member instanceof Member;

        if ($preExistingMember && $member->hasNotBeenDeclaredAsNotFound()) {
            return $member;
        }

        try {
            $twitterUser = $this->accessor->getMemberProfile(
                $memberIdentity->screenName()
            );
        } catch (UnavailableResourceException $exception) {
            $this->handleUnavailableResourceException($memberIdentity, $exception);
        }

        if (!isset($twitterUser)) {
            throw new UnexpectedApiResponseException(
                'An unexpected error has occurred.',
                self::UNEXPECTED_ERROR
            );
        }

        if (!$preExistingMember) {
            return $this->memberRepository->saveMemberWithAdditionalProps(
                $memberIdentity,
                $twitterUser
            );
        }

        $member = $member->setTwitterUsername($twitterUser->screenName());

        return $this->memberRepository->declareUserAsFound($member);
    }
}
