<?php
declare(strict_types=1);

namespace App\Conversation\Producer;

use App\Membership\Model\Member;
use App\Twitter\Exception\UnavailableResourceException;
use Exception;
use stdClass;

trait MemberAwareTrait
{
    /**
     * @param                              $friend
     * @param UnavailableResourceException $exception
     *
     * @throws Exception
     */
    protected function handleUnavailableResourceException(
        $friend,
        UnavailableResourceException $exception
    ) {
        if (
            $exception->getCode() === $this->accessor->getMemberNotFoundErrorCode()
            || $exception->getCode() === $this->accessor->getUserNotFoundErrorCode()
        ) {
            $message = sprintf('User with screen name %s can not be found', $friend->screen_name);
            $this->logger->info($message);

            throw new Exception($message, self::NOT_FOUND_MEMBER);
        } else {
            if ($exception->getCode() === $this->accessor->getSuspendedUserErrorCode()) {
                $message = sprintf(
                    'User with screen name "%s" has been suspended (code: %d, message: "%s")',
                    $friend->screen_name,
                    $exception->getCode(),
                    $exception->getMessage()
                );
                $this->logger->error($message);
                $this->makeUser(
                    (object) ['screen_name' => $friend->screen_name],
                    $friend,
                    $protected = false,
                    $suspended = true
                );
                throw new Exception($message, self::SUSPENDED_USER);
            } elseif ($exception->getCode() === $this->accessor->getProtectedAccountErrorCode()) {
                $message = sprintf(
                    'User with screen name "%s" has a protected account (code: %d, message: "%s")',
                    $friend->screen_name,
                    $exception->getCode(),
                    $exception->getMessage()
                );
                $this->logger->error($message);
                $this->makeUser(
                    (object) ['screen_name' => $friend->screen_name],
                    $friend,
                    $protected = true
                );
                throw new Exception($message, self::PROTECTED_ACCOUNT);
            } else {
                $message = sprintf(
                    'Unavailable resource for user with screen name %s (code: %d, message: "%s")',
                    $friend->screen_name,
                    $exception->getCode(),
                    $exception->getMessage()
                );
                $this->logger->error($message);
                throw new Exception($message, self::UNAVAILABLE_RESOURCE);
            }
        }
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
     * @param stdClass $friend
     *
     * @return Member
     * @throws Exception
     */
    private function getMessageMember(stdClass $friend)
    {
        /** @var Member $member */
        $member            = $this->userRepository->findOneBy(['twitterID' => $friend->id]);
        $preExistingMember = $member instanceof Member;

        if ($preExistingMember && $member->hasNotBeenDeclaredAsNotFound()) {
            return $member;
        }

        try {
            $twitterUser = $this->accessor->showUser($friend->screen_name);
        } catch (UnavailableResourceException $exception) {
            $this->handleUnavailableResourceException($friend, $exception);
        }

        if (!isset($twitterUser)) {
            throw new Exception(
                'An unexpected error has occurred.',
                self::UNEXPECTED_ERROR
            );
        }

        if (!$preExistingMember) {
            return $this->makeUser($twitterUser, $friend);
        }

        $member = $member->setTwitterUsername($twitterUser->screen_name);

        return $this->userRepository->declareUserAsFound($member);
    }
}
