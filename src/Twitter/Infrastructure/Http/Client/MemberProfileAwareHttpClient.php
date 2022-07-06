<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Membership\Domain\Exception\InvalidMemberException;
use App\Membership\Domain\Model\Member;
use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;
use App\Twitter\Domain\Http\Resource\UnavailableResourceHandlerInterface;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\UnavailableResource;

class MemberProfileAwareHttpClient implements MemberProfileAwareHttpClientInterface
{
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;

    private HttpClientInterface $accessor;

    private UnavailableResourceHandlerInterface $unavailableResourceHandler;

    public function __construct(
        HttpClientInterface                 $accessor,
        MemberRepositoryInterface           $memberRepository,
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
     * @throws UnexpectedApiResponseException
     * @throws \App\Membership\Domain\Exception\MembershipException
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

        $member = $member->setTwitterScreenName($memberIdentity->screenName());

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