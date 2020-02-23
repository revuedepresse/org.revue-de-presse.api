<?php
declare(strict_types=1);

namespace App\Conversation\Producer;

use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Membership\Model\Member;
use App\Twitter\Api\Resource\MemberIdentity;
use App\Twitter\Exception\UnavailableResourceException;
use Exception;
use stdClass;

trait MemberAwareTrait
{
    /**
     * @param                              $memberIdentity
     * @param UnavailableResourceException $exception
     *
     * @throws Exception
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

            throw new Exception($message, self::NOT_FOUND_MEMBER);
        }

        if ($exception->getCode() === $this->accessor->getSuspendedUserErrorCode()) {
            $message = sprintf(
                'User with screen name "%s" has been suspended (code: %d, message: "%s")',
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);
            $this->makeUser(
                (object) ['screen_name' => $memberIdentity->screenName()],
                $memberIdentity,
                $protected = false,
                $suspended = true
            );
            throw new Exception($message, self::SUSPENDED_USER);
        }

        if ($exception->getCode() === $this->accessor->getProtectedAccountErrorCode()) {
            $message = sprintf(
                'User with screen name "%s" has a protected account (code: %d, message: "%s")',
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);
            $this->makeUser(
                (object) ['screen_name' => $memberIdentity->screenName()],
                $memberIdentity,
                $protected = true
            );
            throw new Exception($message, self::PROTECTED_ACCOUNT);
        }

        $message = sprintf(
            'Unavailable resource for user with screen name %s (code: %d, message: "%s")',
            $memberIdentity->screenName(),
            $exception->getCode(),
            $exception->getMessage()
        );
        $this->logger->error($message);
        throw new Exception($message, self::UNAVAILABLE_RESOURCE);
    }

    /**
     * @param $exception
     *
     * @return bool
     */
    protected function shouldBreakPublication(Exception $exception)
    {
        return $exception->getCode() === self::UNEXPECTED_ERROR
            || $exception->getCode() === self::UNAVAILABLE_RESOURCE;
    }

    /**
     * @param $exception
     *
     * @return bool
     */
    protected function shouldContinuePublication(Exception $exception)
    {
        return $exception->getCode() === self::NOT_FOUND_MEMBER
            || $exception->getCode() === self::SUSPENDED_USER
            || $exception->getCode() === self::PROTECTED_ACCOUNT;
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
        $member            = $this->userRepository->findOneBy(
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
            return $this->makeUser(
                $twitterUser,
                $memberIdentity
            );
        }

        $member = $member->setTwitterUsername($twitterUser->screenName());

        return $this->userRepository->declareUserAsFound($member);
    }
}
