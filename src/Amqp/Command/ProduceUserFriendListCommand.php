<?php

namespace App\Amqp\Command;

use App\Operation\OperationClock;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Translation\TranslatorInterface;

use WeavingTheWeb\Bundle\AmqpBundle\Exception\SkippableMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException,
    WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;

use App\Membership\Entity\Member;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceUserFriendListCommand extends AggregateAwareCommand
{
    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    private $producer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var OperationClock
     */
    public $operationClock;

    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:user_timeline')
            ->setDescription('Produce a message to get a user timeline')
            ->addOption(
            'oauth_token',
            null,
            InputOption::VALUE_OPTIONAL,
            'A token is required'
        )->addOption(
            'oauth_secret',
            null,
            InputOption::VALUE_OPTIONAL,
            'A secret is required'
        )->addOption(
            'screen_name',
            null,
            InputOption::VALUE_REQUIRED,
            'The screen name of a user'
        )->addOption(
            'routing_key',
            null,
            InputOption::VALUE_OPTIONAL,
            'A producer key'
        )
        ->addOption(
            'producer',
            null,
            InputOption::VALUE_OPTIONAL,
            'A producer key',
            'twitter.user_status'
        )->setAliases(array('wtw:amqp:tw:prd:utl'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|mixed|null
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->get('operation.clock')->shouldSkipOperation()) {
            return self::RETURN_STATUS_SUCCESS;
        }

        $this->input = $input;
        $this->output = $output;

        $messageBody = $this->getTokensFromInput();

        if (array_key_exists('screen_name', $messageBody)) {
            $assumedScreenName = $messageBody['screen_name'];
        } else {
            $assumedScreenName = null;
        }

        $this->setUpDependencies();

        $invalidUsers = 0;

        $friends = $this->accessor->showUserFriends($this->input->getOption('screen_name'));
        foreach ($friends->ids as $twitterUserId) {
            $foundMember = $this->userRepository->findOneBy(['twitterID' => $twitterUserId]);
            $preExistingMember = $foundMember instanceof User;

            if ($preExistingMember && !$foundMember->isNotFound()) {
                $member = $foundMember;
            } else {
                try {
                    $member = $this->saveMemberWithTwitterId($twitterUserId, $foundMember);
                } catch (SuspendedAccountException $exception) {
                    $this->handleSuspendedMemberException($twitterUserId, $assumedScreenName, $exception);

                    $invalidUsers++;

                    continue;
                } catch (ProtectedAccountException $exception) {
                    $this->handleProtectedMemberException($twitterUserId, $assumedScreenName, $exception);

                    $invalidUsers++;

                    continue;
                } catch (UnavailableResourceException $exception) {
                    $this->handleUnavailableResourceException($twitterUserId, $exception);

                    return $exception->getCode();
                }
            }

            if (isset($member)) {
                try {
                    $this->handlePreExistingMember($member, $member->getTwitterUsername(), $messageBody);
                } catch (SkippableMemberException $exception) {
                    continue;
                }
            }
        }

        $output->writeln(
            $this->translator->trans(
                'amqp.production.friendlist.success',
                ['{{ count }}' => count($friends->ids) - $invalidUsers],
                'messages'
            )
        );
    }

    /**
     * @param $message
     * @param $level
     * @param \Exception $exception
     */
    protected function sendMessage($message, $level, \Exception $exception)
    {
        $this->output->writeln($message);
        $this->logger->$level($exception->getMessage());
    }

    private function extractRoutingKeyFromOptions(): void
    {
        if ($this->input->hasOption('routing_key') && !is_null($this->input->getOption('routing_key'))) {
            $this->routingKey = $this->input->getOption('routing_key');
        } else {
            $this->routingKey = '';
        }
    }

    private function setProducer(): void
    {
        $producerKey = $this->input->getOption('producer');

        /** @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer */
        $this->producer = $this->getContainer()->get(sprintf(
            'old_sound_rabbit_mq.weaving_the_web_amqp.%s_producer', $producerKey
        ));
    }

    private function setUpDependencies()
    {
        $this->setProducer();
        $this->extractRoutingKeyFromOptions();

        $tokens = $this->getTokensFromInput();

        $this->setUpLogger();
        $this->setupAccessor($tokens);
        $this->setupAggregateRepository();

        $this->translator = $this->getContainer()->get('translator');
    }

    /**
     * @param $member
     * @param $assumedScreenName
     * @param $messageBody
     * @throws SkippableMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handlePreExistingMember(User $member, $assumedScreenName, $messageBody)
    {
        $twitterUsername = $member->getTwitterUsername();

        $this->guardAgainstMembersWhichShouldBeSkipped($member, $assumedScreenName);

        $aggregate = $this->getListAggregateByName($twitterUsername, 'user :: ' . $twitterUsername);

        $messageBody['aggregate_id'] = $aggregate->getId();
        $messageBody['screen_name'] = $twitterUsername;

        $this->producer->setContentType('application/json');
        $this->producer->publish(serialize(json_encode($messageBody)), $this->routingKey);

        $publishedMessage = $this->translator->trans(
            'amqp.info.message_published',
            ['{{ user }}' => $messageBody['screen_name']],
            'messages'
        );
        $this->logger->info($publishedMessage);
    }

    /**
     * @param $twitterUserId
     * @param $assumedScreenName
     * @param $exception
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleProtectedMemberException($twitterUserId, $assumedScreenName, $exception)
    {
        $member = $this->userRepository->make(
            $twitterUserId,
            $screenName = $assumedScreenName,
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
     * @param $twitterUserId
     * @param $exception
     */
    private function handleUnavailableResourceException($twitterUserId, $exception): void
    {
        $unavailableResource = $this->translator->trans(
            'amqp.output.unavailable_resource',
            ['{{ user }}' => $twitterUserId],
            'messages'
        );
        $this->sendMessage($unavailableResource, 'error', $exception);
    }

    /**
     * @param $twitterUserId
     * @param $assumedScreenName
     * @param $exception
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleSuspendedMemberException($twitterUserId, $assumedScreenName, $exception)
    {
        $member = $this->userRepository->make(
            $twitterUserId,
            $screenName = $assumedScreenName,
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
     * @param $twitterUserId
     * @param $prexistingMember
     * @return User
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     */
    private function saveMemberWithTwitterId($twitterUserId, $prexistingMember): User
    {
        $twitterUser = $this->accessor->showUser($twitterUserId);

        if (isset($twitterUser->screen_name)) {
            $member = new User();

            if ($prexistingMember instanceof User) {
                $member = $prexistingMember;
                $member->setNotFound(false);
            }

            $member->setTwitterUsername($twitterUser->screen_name);
            $member->setTwitterID($twitterUserId);
            $member->setEnabled(false);
            $member->setLocked(false);
            $member->setEmail('@' . $twitterUser->screen_name);
            $member->setEnabled(false);

            $this->entityManager->persist($member);
            $this->entityManager->flush();
            $publishedMessage = $this->translator->trans(
                'amqp.info.user_persisted',
                ['{{ user }}' => $twitterUser->screen_name],
                'messages'
            );
            $this->logger->info($publishedMessage);
        } else {
            throw new UnavailableResourceException(serialize($twitterUser), 1);
        }

        return $member;
    }

    /**
     * @param User $member
     * @param      $assumedScreenName
     * @throws SkippableMemberException
     */
    private function guardAgainstMembersWhichShouldBeSkipped(User $member, $assumedScreenName): void
    {
        if ($member->isAWhisperer()) {
            $skippedWhispererMessage = $this->translator->trans(
                'amqp.info.skipped_whisperer',
                ['{{ user }}' => $assumedScreenName],
                'messages'
            );
            $this->logger->info($skippedWhispererMessage);

            throw new SkippableMemberException($skippedWhispererMessage);
        }

        if ($member->isProtected()) {
            $protectedAccount = $this->translator->trans(
                'amqp.info.skipped_protected_account',
                ['{{ user }}' => $assumedScreenName],
                'messages'
            );
            $this->logger->info($protectedAccount);

            throw new SkippableMemberException($protectedAccount);
        }

        if ($member->isSuspended()) {
            $suspendedAccount = $this->translator->trans(
                'amqp.info.skipped_suspended_account',
                ['{{ user }}' => $assumedScreenName],
                'messages'
            );
            $this->logger->info($suspendedAccount);

            throw new SkippableMemberException($suspendedAccount);
        }
    }
}
