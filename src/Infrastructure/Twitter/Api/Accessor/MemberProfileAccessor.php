<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Infrastructure\Twitter\Api\UnavailableResource;
use App\Infrastructure\Twitter\Api\UnavailableResourceHandlerInterface;
use App\Domain\Membership\Exception\InvalidMemberException;
use App\Membership\Entity\MemberInterface;
use App\Membership\Model\Member;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Exception\UnavailableResourceException;

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
            $eventRepository = $this->memberProfileCollectedEventRepository;
            $twitterMember = $eventRepository->collectedMemberProfile(
                $this->accessor,
                [$eventRepository::OPTION_SCREEN_NAME => $memberIdentity->screenName()]
            );
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
        $fetchedMember = $this->accessor->showUser($username);
        $member = $this->memberRepository->findOneBy(['twitterID' => $fetchedMember->id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberProfileIsUpToDate($member, $username, $fetchedMember);

            return $member;
        }

        InvalidMemberException::guardAgainstInvalidUsername($username);
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
        $memberBioIsAvailable = $member->isNotSuspended() &&
            $member->isNotProtected() &&
            $member->hasNotBeenDeclaredAsNotFound()
        ;

        if (!$memberBioIsAvailable) {
            return $member;
        }

        if ($remoteMember === null) {
            $remoteMember = $this->accessor->showUser($memberName);
        }

        $member->description = $remoteMember->description;
        $member->url = $remoteMember->url;

        return $this->memberRepository->saveMember($member);
    }
}