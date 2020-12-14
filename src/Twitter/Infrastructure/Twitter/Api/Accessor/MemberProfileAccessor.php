<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\UnexpectedApiResponseException;
use App\Twitter\Domain\Membership\Exception\InvalidMemberException;
use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\Twitter\Api\UnavailableResource;
use App\Twitter\Infrastructure\Twitter\Api\UnavailableResourceHandlerInterface;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Domain\Model\Member;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

class MemberProfileAccessor implements MemberProfileAccessorInterface
{
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;

    private ApiAccessorInterface $accessor;

    private UnavailableResourceHandlerInterface $unavailableResourceHandler;

    public function __construct(
        ApiAccessorInterface $accessor,
        MemberRepositoryInterface $memberRepository,
        UnavailableResourceHandlerInterface $unavailableResourceHandler
    ) {
        $this->accessor                   = $accessor;
        $this->memberRepository           = $memberRepository;
        $this->unavailableResourceHandler = $unavailableResourceHandler;
    }

    /**
     * @param MemberInterface $member
     * @param string          $memberName
     * @param \stdClass|null  $remoteMember
     *
     * @return MemberInterface
     */
    public function ensureMemberProfileIsUpToDate(
        MemberInterface $member,
        string $memberName,
        \stdClass $remoteMember = null
    ): MemberInterface {
        $memberBioIsAvailable = $member->isNotSuspended()
            && $member->isNotProtected()
            && $member->hasNotBeenDeclaredAsNotFound();

        if (!$memberBioIsAvailable) {
            return $member;
        }

        if ($remoteMember === null) {
            $remoteMember = $this->collectedMemberProfile($memberName);
        }

        $member->description = $remoteMember->description;
        $member->url         = $remoteMember->url;

        return $this->memberRepository->saveMember($member);
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws UnexpectedApiResponseException
     * @throws MembershipException
     */
    public function getMemberByIdentity(
        MemberIdentity $memberIdentity
    ): MemberInterface {
        /** @var Member $member */
        $member            = $this->memberRepository->findOneBy(
            ['twitterID' => $memberIdentity->id()]
        );
        $preExistingMember = $member instanceof Member;

        if ($preExistingMember && $member->hasNotBeenDeclaredAsNotFound()) {
            return $member;
        }

        try {
            $twitterMember = $this->collectedMemberProfile($memberIdentity->screenName());
        } catch (UnavailableResourceException $exception) {
            $this->unavailableResourceHandler->handle(
                $memberIdentity,
                UnavailableResource::ofTypeAndRootCause(
                    $exception->getCode(),
                    $exception->getMessage()
                )
            );
        }

        if (!isset($twitterMember)) {
            throw new UnexpectedApiResponseException(
                'An unexpected error has occurred.',
                self::UNEXPECTED_ERROR
            );
        }

        if (!$preExistingMember) {
            return $this->memberRepository->saveMemberFromIdentity(
                $memberIdentity
            );
        }

        $member = $member->setTwitterUsername($memberIdentity->screenName());

        return $this->memberRepository->declareMemberAsFound($member);
    }

    /**
     * @param string $username
     *
     * @return MemberInterface
     * @throws InvalidMemberException
     */
    public function refresh(string $username): MemberInterface
    {
        $fetchedMember = $this->collectedMemberProfile($username);

        $member = $this->memberRepository->findOneBy(['twitterID' => $fetchedMember->id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberProfileIsUpToDate($member, $username, $fetchedMember);

            return $member;
        }

        InvalidMemberException::guardAgainstInvalidUsername($username);
    }

    private function collectedMemberProfile(string $screenName): \stdClass
    {
        $eventRepository = $this->memberProfileCollectedEventRepository;

        return $eventRepository->collectedMemberProfile(
            $this->accessor,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );
    }
}