<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Command;

use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberFriendsCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Membership\Domain\Entity\Legacy\Member;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class FetchMemberSubscriptionTimelineMessageDispatcher extends AggregateAwareCommand
{
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberFriendsCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;
    use MessageBusTrait;
    use TranslatorTrait;

    public function configure(): void
    {
        $this->setName('weaving_the_web:amqp:dispatch:member_subscription_timeline_message')
            ->setDescription('Produce a message to get a user timeline')
            ->addOption(
            'screen_name',
            null,
            InputOption::VALUE_REQUIRED,
            'The screen name of a user'
            )->setAliases(['wtw:amqp:d:m']);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|mixed|null
     * @throws InvalidSerializedTokenException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;


        $this->setUpDependencies();

        $exceptionalMembers = 0;
        $messageBody = $this->getTokensFromInputOrFallback();
        $screenName = $this->input->getOption('screen_name');

        $eventRepository = $this->memberFriendsCollectedEventRepository;
        $friends = $eventRepository->collectedMemberFriends(
            $this->accessor,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );

        foreach ($friends->ids as $twitterUserId) {
            $foundMember = $this->memberRepository->findOneBy(['twitterID' => $twitterUserId]);
            $preExistingMember = $foundMember instanceof MemberInterface;

            if ($preExistingMember && !$foundMember->hasBeenDeclaredAsNotFound()) {
                $member = $foundMember;
            } else {
                try {
                    $member = $this->saveMemberWithTwitterId(
                        (string) $twitterUserId,
                        $foundMember
                    );
                } catch (SuspendedAccountException $exception) {
                    $this->handleSuspendedMemberException($twitterUserId, $screenName, $exception);

                    $exceptionalMembers++;

                    continue;
                } catch (ProtectedAccountException $exception) {
                    $this->handleProtectedMemberException($twitterUserId, $screenName, $exception);

                    $exceptionalMembers++;

                    continue;
                } catch (NotFoundMemberException $exception) {
                    $this->handleNotFoundMemberException($twitterUserId, $screenName, $exception);

                    $exceptionalMembers++;

                    continue;
                } catch (UnavailableResourceException $exception) {
                    $this->handleUnavailableResourceException($twitterUserId, $exception);

                    return $exception->getCode();
                }
            }

            if (isset($member)) {
                try {
                    $this->handlePreExistingMember(
                        $member,
                        $member->getTwitterUsername(),
                        $messageBody
                    );
                } catch (SkippableMemberException $exception) {
                    continue;
                }
            }
        }

        $output->writeln(
            $this->translator->trans(
                'amqp.production.friendlist.success',
                ['{{ count }}' => count($friends->ids) - $exceptionalMembers],
                'messages'
            )
        );

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @param $message
     * @param $level
     * @param Exception $exception
     */
    protected function sendMessage($message, $level, Exception $exception)
    {
        $this->output->writeln($message);
        $this->logger->$level($exception->getMessage());
    }

    /**
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        $tokens = $this->getTokensFromInputOrFallback();


        $this->accessor->setAccessToken(Token::fromArray($tokens));

        // noop
        $this->setUpLogger();
        $this->setupAggregateRepository();
    }

    /**
     * @param $member
     * @param $screenName
     * @param $messageBody
     * @throws SkippableMemberException
     */
    private function handlePreExistingMember(
        MemberInterface $member,
        $screenName,
        $messageBody
    ): void {
        $twitterUsername = $member->getTwitterUsername();

        $this->guardAgainstMembersWhichShouldBeSkipped($member, $screenName);

        $aggregate = $this->getListAggregateByName($twitterUsername, 'user :: ' . $twitterUsername);

        $messageBody['aggregate_id'] = $aggregate->getId();
        $messageBody['screen_name'] = $twitterUsername;

        $message = new FetchMemberStatus(
            $twitterUsername,
            $aggregate->getId(),
            (new Token)
                ->setOAuthToken($this->getOAuthToken())
                ->setOAuthSecret($this->getOAuthSecret())
        );

        $this->dispatcher->dispatch($message);

        $publishedMessage = $this->translator->trans(
            'amqp.info.message_published',
            ['{{ user }}' => $messageBody['screen_name']],
            'messages'
        );
        $this->logger->info($publishedMessage);
    }

    /**
     * @param string $twitterUserId
     * @param string $screenName
     * @param        $exception
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleProtectedMemberException(
        string $twitterUserId,
        string $screenName,
        $exception
    ): void {
        $member = $this->memberRepository->make(
            $twitterUserId,
            $screenName,
            $protected = true
        );
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $protectedAccount = $this->translator->trans(
            'amqp.output.protected_member',
            ['{{ user }}' => $twitterUserId],
            'messages'
        );
        $this->sendMessage($protectedAccount, 'info', $exception);
    }

    /**
     * @param string $twitterUserId
     * @param string $screenName
     * @param        $exception
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleNotFoundMemberException(
        string $twitterUserId,
        string $screenName,
        $exception
    ): void {
        $member = $this->memberRepository->make(
            $twitterUserId,
            $screenName
        );

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->memberRepository->declareMemberAsNotFound($member);

        $protectedAccount = $this->translator->trans(
            'amqp.output.not_found_member',
            ['{{ user }}' => $twitterUserId],
            'messages'
        );
        $this->sendMessage($protectedAccount, 'info', $exception);
    }

    /**
     * @param $twitterUserId
     * @param $exception
     */
    private function handleUnavailableResourceException(
        $twitterUserId,
        $exception
    ): void {
        $unavailableResource = $this->translator->trans(
            'amqp.output.unavailable_resource',
            ['{{ user }}' => $twitterUserId],
            'messages'
        );
        $this->sendMessage($unavailableResource, 'error', $exception);
    }

    /**
     * @param $twitterUserId
     * @param $screenName
     * @param $exception
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleSuspendedMemberException(
        $twitterUserId,
        $screenName,
        $exception
    ): void {
        $member = $this->memberRepository->make(
            $twitterUserId,
            $screenName,
            $protected = false,
            $suspended = true
        );
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $suspendedAccount = $this->translator->trans(
            'amqp.output.suspended_account',
            ['{{ user }}' => $twitterUserId],
            'messages'
        );
        $this->sendMessage($suspendedAccount, 'info', $exception);
    }

    /**
     * @param string $memberIdentifier
     * @param        $prexistingMember
     *
     * @return MemberInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws UnavailableResourceException
     */
    private function saveMemberWithTwitterId(
        string $memberIdentifier,
        $prexistingMember
    ): MemberInterface {
        $eventRepository = $this->memberProfileCollectedEventRepository;
        $twitterMember = $eventRepository->collectedMemberProfile(
            $this->accessor,
            [$eventRepository::OPTION_SCREEN_NAME => $memberIdentifier]
        );

        if (isset($twitterMember->screen_name)) {
            $member = new Member();

            if ($prexistingMember instanceof MemberInterface) {
                $member = $prexistingMember;
                $member->setNotFound(false);
            }

            $member->setTwitterUsername($twitterMember->screen_name);
            $member->setTwitterID($memberIdentifier);
            $member->setEnabled(false);
            $member->setLocked(false);
            $member->setEmail('@' . $twitterMember->screen_name);
            $member->setEnabled(false);

            $this->entityManager->persist($member);
            $this->entityManager->flush();
            $publishedMessage = $this->translator->trans(
                'amqp.info.user_persisted',
                ['{{ user }}' => $twitterMember->screen_name],
                'messages'
            );
            $this->logger->info($publishedMessage);
        } else {
            throw new UnavailableResourceException(serialize($twitterMember), 1);
        }

        return $member;
    }

    /**
     * @param MemberInterface $member
     * @param string          $screenName
     *
     * @throws SkippableMemberException
     */
    private function guardAgainstMembersWhichShouldBeSkipped(
        MemberInterface $member,
        string $screenName
    ): void {
        if ($member->isAWhisperer()) {
            $skippedWhispererMessage = $this->translator->trans(
                'amqp.info.skipped_whisperer',
                ['{{ user }}' => $screenName],
                'messages'
            );
            $this->logger->info($skippedWhispererMessage);
        }

        if ($member->isProtected()) {
            $protectedAccount = $this->translator->trans(
                'amqp.info.skipped_protected_account',
                ['{{ user }}' => $screenName],
                'messages'
            );
            $this->logger->info($protectedAccount);

            throw new SkippableMemberException($protectedAccount);
        }

        if ($member->isSuspended()) {
            $suspendedAccount = $this->translator->trans(
                'amqp.info.skipped_suspended_account',
                ['{{ user }}' => $screenName],
                'messages'
            );
            $this->logger->info($suspendedAccount);

            throw new SkippableMemberException($suspendedAccount);
        }
    }
}
